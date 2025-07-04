<?php
declare(strict_types=1);
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;

/**
 * Middleware that counts the number of executed queries.
 */
class QueryCounterMiddleware implements MiddlewareInterface
{
    /** Number of processed messages. */
    private int $count = 0;

    /**
     * Increment the internal counter on each query execution.
     */
    public function __invoke(MessageInterface $msg): void
    {
        $this->count++;
    }

    /**
     * Get the amount of executed queries.
     */
    public function getQueryCount(): int
    {
        return $this->count;
    }
}
