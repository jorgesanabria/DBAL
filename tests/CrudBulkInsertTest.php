<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use DBAL\Crud;

class CrudBulkInsertTest extends TestCase
{
    private function createPdo()
    {
        return new PDO('sqlite::memory:');
    }

    public function testBulkInsertInsertsMultipleRows()
    {
        $pdo = $this->createPdo();
        $pdo->exec('CREATE TABLE items (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');

        $crud = (new Crud($pdo))->from('items');
        $count = $crud->bulkInsert([
            ['name' => 'A'],
            ['name' => 'B'],
            ['name' => 'C'],
        ]);
        $this->assertEquals(3, $count);
        $rows = iterator_to_array($crud->select());
        $this->assertCount(3, $rows);
    }
}
