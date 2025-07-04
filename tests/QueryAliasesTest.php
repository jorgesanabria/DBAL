<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use DBAL\Crud;

class QueryAliasesTest extends TestCase
{
    public function testTakeAndSkipAliases()
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE t (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        $pdo->exec('INSERT INTO t(name) VALUES ("A"),("B"),("C")');

        $crud = (new Crud($pdo))->from('t');
        $rows = iterator_to_array($crud->take(2)->skip(1)->select('name'));
        $this->assertEquals([['name' => 'B'], ['name' => 'C']], $rows);
    }
}
