<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use DBAL\Crud;
use DBAL\QueryCounterMiddleware;

class QueryCounterMiddlewareTest extends TestCase
{
    private function createPdo()
    {
        return new PDO('sqlite::memory:');
    }

    public function testCounterIncrements()
    {
        $pdo = $this->createPdo();
        $pdo->exec('CREATE TABLE items (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');

        $counter = new QueryCounterMiddleware();
        $crud = (new Crud($pdo))->from('items')->withMiddleware($counter);

        iterator_to_array($crud->select());
        $this->assertEquals(1, $counter->getQueryCount());

        $crud->insert(['name' => 'A']);
        $this->assertEquals(2, $counter->getQueryCount());

        $crud->where(['id__eq' => 1])->update(['name' => 'B']);
        $this->assertEquals(3, $counter->getQueryCount());

        $crud->where(['id__eq' => 1])->delete();
        $this->assertEquals(4, $counter->getQueryCount());
    }
}
