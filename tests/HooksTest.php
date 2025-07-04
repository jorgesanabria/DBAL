<?php
use PHPUnit\Framework\TestCase;
use DBAL\Crud;
use DBAL\ActiveRecord;
use DBAL\TransactionMiddleware;
use DBAL\UnitOfWorkMiddleware;
use function DBAL\Hooks\useCrud;
use function DBAL\Hooks\useCache;
use function DBAL\Hooks\useTransaction;
use function DBAL\Hooks\useUnitOfWork;
use function DBAL\Hooks\useActiveRecord;

class HooksTest extends TestCase
{
    private function createPdo()
    {
        return new PDO('sqlite::memory:');
    }

    public function testUseCrudCreatesCrud()
    {
        $pdo = $this->createPdo();
        $pdo->exec('CREATE TABLE items (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        $crud = useCrud($pdo, 'items');
        $crud->insert(['name' => 'A']);
        $rows = iterator_to_array($crud->select());
        $this->assertCount(1, $rows);
    }

    public function testUseCacheCachesResults()
    {
        $pdo = $this->createPdo();
        $pdo->exec('CREATE TABLE test (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        $pdo->exec('INSERT INTO test(name) VALUES ("A")');
        $crud = useCrud($pdo, 'test');
        $crud = useCache($crud);
        $rows = iterator_to_array($crud->select());
        $this->assertEquals('A', $rows[0]['name']);
        $pdo->exec('DELETE FROM test');
        $rows = iterator_to_array($crud->select());
        $this->assertEquals('A', $rows[0]['name']);
    }

    public function testUseTransactionReturnsMiddleware()
    {
        $pdo = $this->createPdo();
        $pdo->exec('CREATE TABLE t (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        $crud = useCrud($pdo, 't');
        [$crud, $tx] = useTransaction($crud);
        $this->assertInstanceOf(TransactionMiddleware::class, $tx);
        $crud->begin();
        $crud->insert(['name' => 'A']);
        $crud->commit();
        $rows = iterator_to_array($crud->select());
        $this->assertCount(1, $rows);
        $this->assertTrue($tx->getLog()[0]);
    }

    public function testUseUnitOfWorkWorks()
    {
        $pdo = $this->createPdo();
        $pdo->exec('CREATE TABLE items (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        $crud = useCrud($pdo, 'items');
        [$crud, $uow] = useUnitOfWork($crud);
        $this->assertInstanceOf(UnitOfWorkMiddleware::class, $uow);
        $crud->registerNew('items', ['name' => 'B']);
        $crud->commit();
        $rows = iterator_to_array($crud->select());
        $this->assertCount(1, $rows);
    }

    public function testUseActiveRecordReturnsActiveRecordRows()
    {
        $pdo = $this->createPdo();
        $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        $pdo->exec('INSERT INTO users(name) VALUES ("A")');
        $crud = useCrud($pdo, 'users');
        $crud = useActiveRecord($crud);
        $rows = iterator_to_array($crud->select());
        $this->assertInstanceOf(ActiveRecord::class, $rows[0]);
    }
}
