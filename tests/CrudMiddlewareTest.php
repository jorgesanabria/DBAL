<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use DBAL\Crud;

class CrudMiddlewareTest extends TestCase
{
    private function createPdo()
    {
        return new PDO('sqlite::memory:');
    }

    public function testMiddlewaresAreInvoked()
    {
        $pdo = $this->createPdo();
        $pdo->exec('CREATE TABLE test (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');

        $count = 0;
        $mw = function ($msg) use (&$count) {
            $count++;
        };

        $crud = (new Crud($pdo))->from('test')->withMiddleware($mw);

        $id = $crud->insert(['name' => 'A']);
        iterator_to_array($crud->select());
        $crud->where(['id__eq' => $id])->update(['name' => 'B']);
        $crud->where(['id__eq' => $id])->delete();

        $this->assertEquals(4, $count);
    }
}
