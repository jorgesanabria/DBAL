<?php
use PHPUnit\Framework\TestCase;
use DBAL\Crud;

class CrudFetchAllTest extends TestCase
{
    private function pdoWithData()
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE t (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        $pdo->exec('INSERT INTO t(name) VALUES ("A"), ("B")');
        return $pdo;
    }

    public function testFetchAllReturnsAllRows()
    {
        $crud = (new Crud($this->pdoWithData()))->from('t');
        $rows = $crud->fetchAll('name');
        $this->assertEquals([
            ['name' => 'A'],
            ['name' => 'B'],
        ], $rows);
    }
}
