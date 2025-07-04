<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use DBAL\QueueEventMiddleware;

class QueueEventMiddlewareTest extends TestCase
{
    public function testEventsArePublished()
    {
        $events = [];
        $publisher = function (string $topic, array $payload) use (&$events) {
            $events[] = [$topic, $payload];
        };

        $mw = new QueueEventMiddleware($publisher, 'events');

        $mw->afterInsert('t', ['f' => 'v'], 1);
        $mw->afterBulkInsert('t', [['f' => 'v']], 1);
        $mw->afterUpdate('t', ['f' => 'x'], 2);
        $mw->afterDelete('t', 3);

        $this->assertCount(4, $events);
        $this->assertEquals('insert', $events[0][1]['action']);
        $this->assertEquals('bulkInsert', $events[1][1]['action']);
        $this->assertEquals('update', $events[2][1]['action']);
        $this->assertEquals('delete', $events[3][1]['action']);
    }
}
