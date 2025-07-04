<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use DBAL\Crud;
use DBAL\TransactionMiddleware;
use DBAL\UnitOfWorkMiddleware;

class TransactionUnitOfWorkMiddlewareTest extends TestCase
{
    private function createPdo()
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE items (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        return $pdo;
    }

    public function testCommitRunsInsideTransaction()
    {
        $pdo = $this->createPdo();
        $tx = new TransactionMiddleware($pdo);
        $uow = new UnitOfWorkMiddleware($tx);
        $crud = (new Crud($pdo))->from('items')->withMiddleware($uow)->withMiddleware($tx);

        $crud->registerNew('items', ['name' => 'A']);
        $crud->commit();

        $rows = iterator_to_array($crud->select());
        $this->assertCount(1, $rows);
        $this->assertTrue($tx->getLog()[0]);
    }

    public function testRollbackUndoesChanges()
    {
        $pdo = $this->createPdo();
        $tx = new TransactionMiddleware($pdo);
        $uow = new UnitOfWorkMiddleware($tx);
        $crud = (new Crud($pdo))->from('items')->withMiddleware($uow)->withMiddleware($tx);

        $crud->begin();
        $crud->insert(['name' => 'A']);
        $crud->rollback();

        $rows = iterator_to_array($crud->select());
        $this->assertCount(0, $rows);
    }
}
