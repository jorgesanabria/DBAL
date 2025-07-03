<?php
use PHPUnit\Framework\TestCase;
use DBAL\Crud;
use DBAL\GlobalFilterMiddleware;
use DBAL\QueryBuilder\MessageInterface;

class GlobalFilterMiddlewareTest extends TestCase
{
    private function createPdo()
    {
        return new PDO('sqlite::memory:');
    }

    public function testGlobalFilterIsApplied()
    {
        $pdo = $this->createPdo();
        $pdo->exec('CREATE TABLE items (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, deleted INTEGER)');
        $pdo->exec('INSERT INTO items(name, deleted) VALUES ("A",0),("B",1)');

        $mw = new GlobalFilterMiddleware([], [
            function (MessageInterface $m) {
                return stripos($m->readMessage(), 'WHERE') !== false
                    ? $m->replace('WHERE', 'WHERE deleted = 0 AND')
                    : $m->insertAfter('WHERE deleted = 0');
            }
        ]);

        $log = [];
        $logger = function (MessageInterface $m) use (&$log) { $log[] = $m->readMessage(); };

        $crud = (new Crud($pdo))
            ->from('items')
            ->withMiddleware($mw)
            ->withMiddleware($logger);

        $rows = iterator_to_array($crud->select());

        $this->assertCount(1, $rows);
        $this->assertStringContainsString('deleted = 0', $log[0]);
    }

    public function testTableFilterIsAppliedOnlyForMatchingTable()
    {
        $pdo = $this->createPdo();
        $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, deleted INTEGER)');
        $pdo->exec('CREATE TABLE posts (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT)');
        $pdo->exec('INSERT INTO users(name, deleted) VALUES ("A",0),("B",1)');
        $pdo->exec('INSERT INTO posts(title) VALUES ("P1"),("P2")');

        $mw = new GlobalFilterMiddleware([
            'users' => [
                function (MessageInterface $m) {
                    return stripos($m->readMessage(), 'WHERE') !== false
                        ? $m->replace('WHERE', 'WHERE deleted = 0 AND')
                        : $m->insertAfter('WHERE deleted = 0');
                }
            ]
        ]);

        $log = [];
        $logger = function (MessageInterface $m) use (&$log) { $log[] = $m->readMessage(); };

        $usersCrud = (new Crud($pdo))
            ->from('users')
            ->withMiddleware($mw)
            ->withMiddleware($logger);

        $postsCrud = (new Crud($pdo))
            ->from('posts')
            ->withMiddleware($mw)
            ->withMiddleware($logger);

        $userRows = iterator_to_array($usersCrud->select());
        $postRows = iterator_to_array($postsCrud->select());

        $this->assertCount(1, $userRows);
        $this->assertCount(2, $postRows);
        $this->assertStringContainsString('deleted = 0', $log[0]);
        $this->assertStringNotContainsString('deleted = 0', $log[1]);
    }
}
