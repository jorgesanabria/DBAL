<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use DBAL\Crud;

class CrudStreamTest extends TestCase
{
    private function pdoWithData()
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE t (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        $pdo->exec('INSERT INTO t(name) VALUES ("A"), ("B")');
        return $pdo;
    }

    public function testStreamReturnsGenerator()
    {
        $crud = (new Crud($this->pdoWithData()))->from('t');
        $gen = $crud->stream('name');
        $this->assertInstanceOf(Generator::class, $gen);
        $rows = iterator_to_array($gen);
        $this->assertEquals([
            ['name' => 'A'],
            ['name' => 'B'],
        ], $rows);
    }

    public function testStreamWithCallback()
    {
        $pdo = $this->pdoWithData();
        $crud = (new Crud($pdo))->from('t');
        $count = 0;
        $gen = $crud->stream(function ($row) use (&$count) {
            $count++;
        }, 'name');
        foreach ($gen as $_) {
            // iterate
        }
        $this->assertEquals(2, $count);
    }
}
