<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use DBAL\Crud;
use DBAL\QueryTimingMiddleware;

class QueryTimingMiddlewareTest extends TestCase
{
    private function createPdo()
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE t (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        return $pdo;
    }

    public function testTimingsAreRecorded()
    {
        $pdo = $this->createPdo();
        $mw  = new QueryTimingMiddleware();
        $crud = (new Crud($pdo))->from('t')->withMiddleware($mw);

        $id = $crud->insert(['name' => 'A']);
        iterator_to_array($crud->select());
        $crud->where(['id__eq' => $id])->update(['name' => 'B']);
        $crud->where(['id__eq' => $id])->delete();

        $timings = $mw->getTimings();
        $this->assertCount(4, $timings);
        foreach ($timings as $t) {
            $this->assertArrayHasKey('message', $t);
            $this->assertArrayHasKey('time', $t);
            $this->assertGreaterThanOrEqual(0, $t['time']);
        }
    }

    public function testStreamExecutionIsTimed()
    {
        $pdo = $this->createPdo();
        $pdo->exec('INSERT INTO t(name) VALUES ("A"), ("B")');
        $mw  = new QueryTimingMiddleware();
        $crud = (new Crud($pdo))->from('t')->withMiddleware($mw);

        $gen = $crud->stream('name');
        iterator_to_array($gen);

        $timings = $mw->getTimings();
        $this->assertCount(1, $timings);
        $this->assertGreaterThanOrEqual(0, $timings[0]['time']);
    }
}
