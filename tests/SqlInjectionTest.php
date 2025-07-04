<?php
use PHPUnit\Framework\TestCase;
use DBAL\Crud;
use DBAL\ODataMiddleware;

class SqlInjectionTest extends TestCase
{
    private function createPdo()
    {
        return new PDO('sqlite::memory:');
    }

    public function testODataMiddlewareIgnoresInjection()
    {
        $pdo = $this->createPdo();
        $pdo->exec('CREATE TABLE items (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        $pdo->exec('INSERT INTO items(name) VALUES ("Item")');

        $crud = (new Crud($pdo))->from('items');
        $mw = new ODataMiddleware();

        $query = '$filter=id eq 1; DROP TABLE items';
        $mw->apply($crud, $query);

        $rows = iterator_to_array($crud->select());
        $this->assertCount(1, $rows);
    }
}
