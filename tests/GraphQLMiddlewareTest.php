<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use DBAL\Crud;
use DBAL\GraphQLMiddleware;

class GraphQLMiddlewareTest extends TestCase
{
    private function createPdo()
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE items (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        return $pdo;
    }

    public function testReadInsertUpdateDelete()
    {
        $pdo = $this->createPdo();
        $crud = (new Crud($pdo))->from('items');
        $mw = new GraphQLMiddleware();
        $crud = $mw->attach($crud);

        $mw->handle('mutation { insert(data: {name: "A"}) }');
        $mw->handle('mutation { insert(data: {name: "B"}) }');

        $rows = $mw->handle('{ read { name } }');
        $this->assertCount(2, $rows['data']['read']);

        $mw->handle('mutation { update(id: 1, data: {name: "C"}) }');
        $row = $mw->handle('{ read(filter: {id__eq: 1}) { name } }');
        $this->assertEquals('C', $row['data']['read'][0]['name']);

        $mw->handle('mutation { delete(id: 1) }');
        $rows = $mw->handle('{ read { id } }');
        $this->assertCount(1, $rows['data']['read']);
    }
}
