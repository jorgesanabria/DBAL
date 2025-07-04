<?php
declare(strict_types=1);
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;

class QueryTimingMiddleware implements MiddlewareInterface, AfterExecuteMiddlewareInterface
{
    private array $timings = [];

    public function __invoke(MessageInterface $msg): void
    {
        // no-op
    }

    public function afterExecute(MessageInterface $msg, float $time): void
    {
        $this->timings[] = [
            'message' => $msg->readMessage(),
            'time'    => $time,
        ];
    }

    public function getTimings(): array
    {
        return $this->timings;
    }
}
