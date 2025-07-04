<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use DBAL\Crud;
use DBAL\RxMiddleware;

class RxMiddlewareTest extends TestCase
{
    private function createCrud()
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE items (id INTEGER PRIMARY KEY AUTOINCREMENT, val INTEGER)');
        $pdo->exec('INSERT INTO items(val) VALUES (1),(2),(3)');
        $mw = new RxMiddleware();
        $crud = (new Crud($pdo))->from('items')->withMiddleware($mw);
        return [$crud, $mw];
    }

    public function testMapFilterReduce()
    {
        [$crud, $rx] = $this->createCrud();
        $mapped = iterator_to_array($rx->map($crud, fn($r) => $r['val'] * 2));
        $this->assertEquals([2,4,6], $mapped);

        $filtered = iterator_to_array($rx->filter($crud, fn($r) => $r['val'] > 1));
        $this->assertCount(2, $filtered);

        $sum = $rx->reduce($crud, fn($acc, $r) => $acc + $r['val'], 0);
        $this->assertEquals(6, $sum);
    }

    public function testMergeConcat()
    {
        [$crud, $rx] = $this->createCrud();
        $g1 = $crud->stream();
        $g2 = $crud->stream();
        $merged = iterator_to_array($rx->merge($g1, $g2));
        $this->assertCount(6, $merged);

        $concat = iterator_to_array($rx->concat($crud->stream(), $crud->stream()));
        $this->assertCount(6, $concat);
    }
}
