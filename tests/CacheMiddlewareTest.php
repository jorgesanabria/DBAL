<?php
use PHPUnit\Framework\TestCase;
use DBAL\Crud;
use DBAL\CacheMiddleware;
use DBAL\SqliteCacheStorage;
use DBAL\CacheStorageInterface;

class CacheMiddlewareTest extends TestCase
{
    private function createPdo()
    {
        return new PDO('sqlite::memory:');
    }

    public function testMemoryCache()
    {
        $pdo = $this->createPdo();
        $pdo->exec('CREATE TABLE test (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        $pdo->exec('INSERT INTO test(name) VALUES ("A")');

        $cache = new CacheMiddleware();
        $crud = (new Crud($pdo))->from('test')->withMiddleware($cache);

        $rows = iterator_to_array($crud->select());
        $this->assertEquals('A', $rows[0]['name']);

        // Modify DB directly, cache should still return old result
        $pdo->exec('DELETE FROM test');
        $rows = iterator_to_array($crud->select());
        $this->assertEquals('A', $rows[0]['name']);

        // Insert via Crud to invalidate cache
        $crud->insert(['name' => 'B']);
        $rows = iterator_to_array($crud->select());
        $this->assertEquals('B', $rows[0]['name']);
    }

    public function testSqliteCache()
    {
        $file = tempnam(sys_get_temp_dir(), 'cache');
        $pdo = $this->createPdo();
        $pdo->exec('CREATE TABLE test (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        $pdo->exec('INSERT INTO test(name) VALUES ("A")');

        $storage = new SqliteCacheStorage($file);
        $cache = new CacheMiddleware($storage);
        $crud = (new Crud($pdo))->from('test')->withMiddleware($cache);

        $rows = iterator_to_array($crud->select());
        $this->assertEquals('A', $rows[0]['name']);
        $pdo->exec('DELETE FROM test');
        $rows = iterator_to_array($crud->select());
        $this->assertEquals('A', $rows[0]['name']);

        $crud->insert(['name' => 'B']);
        $rows = iterator_to_array($crud->select());
        $this->assertEquals('B', $rows[0]['name']);
        unlink($file);
    }

    public function testCustomStorageIsUsed()
    {
        $pdo = $this->createPdo();
        $pdo->exec('CREATE TABLE test (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        $pdo->exec('INSERT INTO test(name) VALUES ("A")');

        $storage = new class implements CacheStorageInterface {
            public $gets = 0;
            public $sets = 0;
            public $deletes = 0;
            private $data = [];
            public function get(string $key) { $this->gets++; return $this->data[$key] ?? null; }
            public function set(string $key, $value): void { $this->sets++; $this->data[$key] = $value; }
            public function delete(string $key = null): void { $this->deletes++; if ($key === null) { $this->data = []; } else { unset($this->data[$key]); } }
        };

        $cache = new CacheMiddleware($storage);
        $crud = (new Crud($pdo))->from('test')->withMiddleware($cache);

        iterator_to_array($crud->select());
        $this->assertEquals(1, $storage->gets);
        $this->assertEquals(1, $storage->sets);

        iterator_to_array($crud->select());
        $this->assertEquals(2, $storage->gets);
        $this->assertEquals(1, $storage->sets);

        $crud->insert(['name' => 'B']);
        $this->assertEquals(1, $storage->deletes);
    }
}
