<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use DBAL\Crud;
use DBAL\LinqMiddleware;

class LinqMiddlewareTest extends TestCase
{
    private function createPdo()
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE items (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, active INTEGER)');
        $pdo->exec('INSERT INTO items(name, active) VALUES ("A", 1), ("B", 1), ("C", 0)');
        return $pdo;
    }

    private function createCrud(PDO $pdo)
    {
        $mw = new LinqMiddleware();
        return (new Crud($pdo))->from('items')->withMiddleware($mw);
    }

    public function testAnyNoneWithFilters()
    {
        $crud = $this->createCrud($this->createPdo());

        $this->assertTrue($crud->any(['name' => [\DBAL\QueryBuilder\FilterOp::EQ, 'A']]));
        $this->assertFalse($crud->any(['name' => [\DBAL\QueryBuilder\FilterOp::EQ, 'Z']]));
        $this->assertTrue($crud->none(['name' => [\DBAL\QueryBuilder\FilterOp::EQ, 'Z']]));

        $any = $crud->any(function ($f) { $f->condition('name', \DBAL\QueryBuilder\FilterOp::EQ, 'B'); });
        $this->assertTrue($any);
    }

    public function testAllAndNotAll()
    {
        $crud = $this->createCrud($this->createPdo());

        $this->assertFalse($crud->all(['active' => [\DBAL\QueryBuilder\FilterOp::EQ, 1]]));
        $this->assertTrue($crud->notAll(['active' => [\DBAL\QueryBuilder\FilterOp::EQ, 1]]));

        $subset = $crud->where(['active' => [\DBAL\QueryBuilder\FilterOp::EQ, 1]]);
        $this->assertTrue($subset->all(['active' => [\DBAL\QueryBuilder\FilterOp::EQ, 1]]));
    }

    public function testChainedWhereIsPreserved()
    {
        $crud = $this->createCrud($this->createPdo());

        $this->assertFalse($crud->where(['id' => [\DBAL\QueryBuilder\FilterOp::EQ, 999]])->any());
    }

    public function testMaxMinSum()
    {
        $crud = $this->createCrud($this->createPdo());

        $this->assertEquals(3, $crud->max('id'));
        $this->assertEquals(1, $crud->min('id'));
        $this->assertEquals(6.0, $crud->sum('id'));
        $this->assertEquals(2.0, $crud->average('id'));
        $this->assertEquals([1,2,3], $crud->distinct('id'));
    }

    public function testCount()
    {
        $crud = $this->createCrud($this->createPdo());

        $this->assertEquals(3, $crud->count());
        $this->assertEquals(2, $crud->count(['active' => [\DBAL\QueryBuilder\FilterOp::EQ, 1]]));
    }
}
