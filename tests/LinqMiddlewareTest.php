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

        $this->assertTrue($crud->any(['name__eq' => 'A']));
        $this->assertFalse($crud->any(['name__eq' => 'Z']));
        $this->assertTrue($crud->none(['name__eq' => 'Z']));

        $any = $crud->any(function ($f) { $f->name__eq('B'); });
        $this->assertTrue($any);
    }

    public function testAllAndNotAll()
    {
        $crud = $this->createCrud($this->createPdo());

        $this->assertFalse($crud->all(['active__eq' => 1]));
        $this->assertTrue($crud->notAll(['active__eq' => 1]));

        $subset = $crud->where(['active__eq' => 1]);
        $this->assertTrue($subset->all(['active__eq' => 1]));
    }

    public function testChainedWhereIsPreserved()
    {
        $crud = $this->createCrud($this->createPdo());

        $this->assertFalse($crud->where(['id__eq' => 999])->any());
    }

    public function testMaxMinSum()
    {
        $crud = $this->createCrud($this->createPdo());

        $this->assertEquals(3, $crud->max('id'));
        $this->assertEquals(1, $crud->min('id'));
        $this->assertEquals(6.0, $crud->sum('id'));
    }

    public function testCount()
    {
        $crud = $this->createCrud($this->createPdo());

        $this->assertEquals(3, $crud->count());
        $this->assertEquals(2, $crud->count(['active__eq' => 1]));
    }
}
