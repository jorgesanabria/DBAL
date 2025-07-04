<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use DBAL\Crud;
use DBAL\ActiveRecordTrait;

class ItemEntity {
    use ActiveRecordTrait;
    public $id;
    public $name;
}

class CrudObjectOperationsTest extends TestCase
{
    private function createPdo()
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE items (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        return $pdo;
    }

    public function testInsertObjectAssignsIdAndAllowsUpdate()
    {
        $pdo = $this->createPdo();
        $crud = (new Crud($pdo))->from('items');

        $obj = new ItemEntity();
        $obj->name = 'A';
        $crud->insertObject($obj);

        $this->assertEquals(1, $obj->id);
        $obj->name = 'B';
        $obj->update();

        $rows = iterator_to_array($crud->select());
        $this->assertEquals('B', $rows[0]['name']);
    }

    public function testBulkInsertObjectsInsertsAll()
    {
        $pdo = $this->createPdo();
        $crud = (new Crud($pdo))->from('items');

        $a = new ItemEntity();
        $a->name = 'A';
        $b = new ItemEntity();
        $b->name = 'B';

        $count = $crud->bulkInsertObjects([$a, $b]);
        $this->assertEquals(2, $count);

        $rows = iterator_to_array($crud->select());
        $this->assertCount(2, $rows);
    }
}
