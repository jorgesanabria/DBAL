<?php
use PHPUnit\Framework\TestCase;
use DBAL\Crud;

class ResultIteratorGroupByTest extends TestCase
{
    private function pdoWithData()
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE t (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, status TEXT)');
        $pdo->exec('INSERT INTO t(name, status) VALUES ("Alice", "active"), ("Bob", "inactive"), ("Carol", "active")');
        return $pdo;
    }

    public function testGroupByStringKey()
    {
        $pdo = $this->pdoWithData();
        $crud = (new Crud($pdo))->from('t');
        $groups = $crud->select()->groupBy('status');
        $this->assertCount(2, $groups['active']);
        $this->assertCount(1, $groups['inactive']);
    }

    public function testGroupByCallback()
    {
        $pdo = $this->pdoWithData();
        $crud = (new Crud($pdo))->from('t');
        $groups = $crud->select()->groupBy(function ($row) {
            return $row['name'][0];
        });
        $this->assertArrayHasKey('A', $groups);
        $this->assertArrayHasKey('B', $groups);
        $this->assertArrayHasKey('C', $groups);
    }
}
