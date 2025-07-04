<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use DBAL\Crud;
use DBAL\FirstLastMiddleware;

class FirstLastMiddlewareTest extends TestCase
{
    private function createPdo()
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE items (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        return $pdo;
    }

    private function withData(PDO $pdo)
    {
        $pdo->exec('INSERT INTO items(name) VALUES ("A"), ("B"), ("C")');
    }

    public function testFirstRowIsReturned()
    {
        $pdo = $this->createPdo();
        $this->withData($pdo);
        $mw = new FirstLastMiddleware();
        $crud = (new Crud($pdo))->from('items');
        $crud = $mw->attach($crud);

        $row = $crud->first('name');
        $this->assertEquals('A', $row['name']);
    }

    public function testFirstThrowsWhenEmpty()
    {
        $pdo = $this->createPdo();
        $mw = new FirstLastMiddleware();
        $crud = $mw->attach((new Crud($pdo))->from('items'));
        $this->expectException(RuntimeException::class);
        $crud->first();
    }

    public function testFirstOrDefaultReturnsProvidedDefault()
    {
        $pdo = $this->createPdo();
        $mw = new FirstLastMiddleware();
        $crud = $mw->attach((new Crud($pdo))->from('items'));

        $value = $crud->firstOrDefault('default');
        $this->assertEquals('default', $value);

        $call = $crud->firstOrDefault(function(){ return 'callable'; });
        $this->assertEquals('callable', $call);
    }

    public function testLastRowIsReturned()
    {
        $pdo = $this->createPdo();
        $this->withData($pdo);
        $mw = new FirstLastMiddleware();
        $crud = $mw->attach((new Crud($pdo))->from('items'));

        $row = $crud->last('name');
        $this->assertEquals('C', $row['name']);
    }

    public function testLastThrowsWhenEmpty()
    {
        $pdo = $this->createPdo();
        $mw = new FirstLastMiddleware();
        $crud = $mw->attach((new Crud($pdo))->from('items'));
        $this->expectException(RuntimeException::class);
        $crud->last();
    }

    public function testLastOrDefaultReturnsDefault()
    {
        $pdo = $this->createPdo();
        $mw = new FirstLastMiddleware();
        $crud = $mw->attach((new Crud($pdo))->from('items'));

        $value = $crud->lastOrDefault('d');
        $this->assertEquals('d', $value);
    }
}
