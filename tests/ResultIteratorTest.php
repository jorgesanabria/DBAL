<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use DBAL\QueryBuilder\Query;
use DBAL\ResultIterator;
use DBAL\Crud;

class ResultIteratorTest extends TestCase
{
    private function pdoWithData()
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE t (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        $pdo->exec('INSERT INTO t(name) VALUES ("A"), ("B")');
        return $pdo;
    }

    public function testIterator()
    {
        $pdo = $this->pdoWithData();
        $query = (new Query())->from('t')->buildSelect('name');
        $it = new ResultIterator($pdo, $query);
        $names = [];
        foreach ($it as $row) {
            $names[] = $row['name'];
        }
        $this->assertEquals(['A','B'], $names);
    }

    public function testMapping()
    {
        $pdo = $this->pdoWithData();
        $crud = (new Crud($pdo))->from('t')->map(function($row){ return $row['name']; });
        $it = $crud->select('name');
        $values = iterator_to_array($it);
        $this->assertEquals(['A','B'], $values);
    }
}
