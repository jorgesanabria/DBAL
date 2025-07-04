<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use DBAL\Crud;
use DBAL\AbmEventMiddleware;

class AbmEventMiddlewareTest extends TestCase
{
    private function createPdo()
    {
        return new PDO('sqlite::memory:');
    }

    public function testCallbacksAreInvoked()
    {
        $pdo = $this->createPdo();
        $pdo->exec('CREATE TABLE test (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');

        $events = [];
        $mw = new AbmEventMiddleware(
            function ($t, $fields, $id) use (&$events) { $events[] = ['insert', $t, $fields, $id]; },
            function ($t, $fields, $count) use (&$events) { $events[] = ['update', $t, $fields, $count]; },
            function ($t, $count) use (&$events) { $events[] = ['delete', $t, $count]; },
            function ($t, $rows, $count) use (&$events) { $events[] = ['bulk', $t, $rows, $count]; }
        );

        $crud = (new Crud($pdo))->from('test')->withMiddleware($mw);

        $id = $crud->insert(['name' => 'A']);
        $crud->bulkInsert([['name' => 'B']]);
        $crud->where(['id__eq' => $id])->update(['name' => 'C']);
        $crud->where(['id__eq' => $id])->delete();

        $this->assertCount(4, $events);
        $this->assertEquals('insert', $events[0][0]);
        $this->assertEquals('bulk', $events[1][0]);
        $this->assertEquals('update', $events[2][0]);
        $this->assertEquals('delete', $events[3][0]);
    }
}
