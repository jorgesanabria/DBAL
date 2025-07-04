<?php
declare(strict_types=1);
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;

/**
 * Middleware that publishes CRUD events to a message queue.
 */
class QueueEventMiddleware implements CrudEventInterface
{
    private $publisher;
    private string $topic;

    /**
     * @param callable $publisher Function accepting topic and payload.
     * @param string $topic       Destination topic or channel.
     */
    public function __construct(callable $publisher, string $topic)
    {
        $this->publisher = \Closure::fromCallable($publisher);
        $this->topic     = $topic;
    }

    public function __invoke(MessageInterface $msg): void
    {
        // no-op
    }

    public function afterInsert(string $table, array $fields, $id): void
    {
        ($this->publisher)($this->topic, [
            'action' => 'insert',
            'table'  => $table,
            'fields' => $fields,
            'id'     => $id,
        ]);
    }

    public function afterBulkInsert(string $table, array $rows, int $count): void
    {
        ($this->publisher)($this->topic, [
            'action' => 'bulkInsert',
            'table'  => $table,
            'rows'   => $rows,
            'count'  => $count,
        ]);
    }

    public function afterUpdate(string $table, array $fields, int $count): void
    {
        ($this->publisher)($this->topic, [
            'action' => 'update',
            'table'  => $table,
            'fields' => $fields,
            'count'  => $count,
        ]);
    }

    public function afterDelete(string $table, int $count): void
    {
        ($this->publisher)($this->topic, [
            'action' => 'delete',
            'table'  => $table,
            'count'  => $count,
        ]);
    }
}
