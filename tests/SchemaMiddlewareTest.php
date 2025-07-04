<?php
use PHPUnit\Framework\TestCase;
use DBAL\Crud;
use DBAL\SchemaMiddleware;
use DBAL\SqlSchemaTableBuilder;

class SchemaMiddlewareTest extends TestCase
{
    private function createPdo()
    {
        return new PDO('sqlite::memory:');
    }

    public function testCreateAndAlterTable()
    {
        $pdo = $this->createPdo();
        $schema = new SchemaMiddleware($pdo);
        $crud = (new Crud($pdo))->withMiddleware($schema);

        $builder = $crud->createTable('items');
        $this->assertInstanceOf(SqlSchemaTableBuilder::class, $builder);
        $builder->column('id INTEGER PRIMARY KEY AUTOINCREMENT')
                ->column('name TEXT')
                ->execute();


        $pdo->exec("INSERT INTO items (name) VALUES ('A')");

        $crud->alterTable('items')
            ->addColumn('price', 'INTEGER')
            ->execute();

        $stm = $pdo->prepare('INSERT INTO items (name, price) VALUES (?, ?)');
        $stm->execute(['B', 10]);
        $stm = $pdo->prepare('SELECT price FROM items WHERE name = ?');
        $stm->execute(['B']);
        $row = $stm->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals(10, $row['price']);
    }
}
