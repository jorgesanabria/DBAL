<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use DBAL\Crud;
use DBAL\QueryBuilder\Query;

class CrudTest extends TestCase
{
    private function createPdo()
    {
        return new PDO('sqlite::memory:');
    }

    public function testInsertSelectUpdateDelete()
    {
        $pdo = $this->createPdo();
        $pdo->exec('CREATE TABLE test (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');

        $crud = (new Crud($pdo))->from('test');

        $id = $crud->insert(['name' => 'Alice']);
        $this->assertEquals(1, $id);

        $row = iterator_to_array($crud->where(['id__eq' => $id])->select())[0];
        $this->assertEquals('Alice', $row['name']);

        $count = $crud->where(['id__eq' => $id])->update(['name' => 'Bob']);
        $this->assertEquals(1, $count);

        $count = $crud->where(['id__eq' => $id])->delete();
        $this->assertEquals(1, $count);
    }
}
