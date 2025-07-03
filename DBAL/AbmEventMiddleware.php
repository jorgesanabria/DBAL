<?php
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;

class AbmEventMiddleware implements AbmEventInterface
{
    private $onInsert;
    private $onBulkInsert;
    private $onUpdate;
    private $onDelete;

    public function __construct(
        callable $onInsert = null,
        callable $onUpdate = null,
        callable $onDelete = null,
        callable $onBulkInsert = null
    ) {
        $this->onInsert = $onInsert;
        $this->onUpdate = $onUpdate;
        $this->onDelete = $onDelete;
        $this->onBulkInsert = $onBulkInsert;
    }

    public function __invoke(MessageInterface $msg): void
    {
        // no-op
    }

    public function afterInsert(string $table, array $fields, $id): void
    {
        if ($this->onInsert) {
            ($this->onInsert)($table, $fields, $id);
        }
    }

    public function afterBulkInsert(string $table, array $rows, int $count): void
    {
        if ($this->onBulkInsert) {
            ($this->onBulkInsert)($table, $rows, $count);
        }
    }

    public function afterUpdate(string $table, array $fields, int $count): void
    {
        if ($this->onUpdate) {
            ($this->onUpdate)($table, $fields, $count);
        }
    }

    public function afterDelete(string $table, int $count): void
    {
        if ($this->onDelete) {
            ($this->onDelete)($table, $count);
        }
    }
}
