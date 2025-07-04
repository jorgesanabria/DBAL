<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use DBAL\Crud;
use DBAL\LinqMiddleware;
use DBAL\ODataMiddleware;

class SqlInjectionTest extends TestCase
{
    private function createPdo()
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE items (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        $pdo->exec('INSERT INTO items(name) VALUES ("safe")');
        return $pdo;
    }

    public function testInjectionInFilterDoesNotExecute()
    {
        $pdo = $this->createPdo();
        $crud = (new Crud($pdo))->from('items');
        $malicious = "safe'; DROP TABLE items; --";
        $rows = iterator_to_array($crud->where(['name' => [\DBAL\QueryBuilder\FilterOp::EQ, $malicious]])->select());
        $this->assertEmpty($rows);
        $count = (int)$pdo->query('SELECT COUNT(*) FROM items')->fetchColumn();
        $this->assertEquals(1, $count);
    }

    public function testLinqMiddlewareRejectsInvalidIdentifier()
    {
        $pdo = $this->createPdo();
        $crud = (new Crud($pdo))->from('items')->withMiddleware(new LinqMiddleware());
        $this->expectException(InvalidArgumentException::class);
        $crud->max('name; DROP TABLE items; --');
    }

    public function testODataMiddlewareIgnoresInjection()
    {
        $pdo = $this->createPdo();
        $mw = new ODataMiddleware();
        $crud = $mw->attach((new Crud($pdo))->from('items'));
        $rows = $mw->query("\$filter=name eq 'safe'; DROP TABLE items; --");
        $this->assertCount(1, $rows);
        $count = (int)$pdo->query('SELECT COUNT(*) FROM items')->fetchColumn();
        $this->assertEquals(1, $count);
    }

    public function testODataMiddlewareIgnoresEqfInjection()
    {
        $pdo = $this->createPdo();
        $mw = new ODataMiddleware();
        $crud = $mw->attach((new Crud($pdo))->from('items'));
        try {
            $mw->query("\$filter=id eqf '1; DROP TABLE items; --'");
            $this->fail('Exception not thrown');
        } catch (InvalidArgumentException $e) {
            // Expected
        }
        $count = (int)$pdo->query('SELECT COUNT(*) FROM items')->fetchColumn();
        $this->assertEquals(1, $count);
    }
}
