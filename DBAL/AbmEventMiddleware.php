<?php
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;

/**
 * Clase/Interfaz AbmEventMiddleware
 */
class AbmEventMiddleware implements AbmEventInterface
{
    public function __construct(
        private ?callable $onInsert = null,
        private ?callable $onUpdate = null,
        private ?callable $onDelete = null,
        private ?callable $onBulkInsert = null
    ) {
    }

/**
 * __invoke
 * @param MessageInterface $msg
 * @return void
 */

    public function __invoke(MessageInterface $msg): void
    {
        // no-op
    }

/**
 * afterInsert
 * @param string $table
 * @param array $fields
 * @param mixed $id
 * @return void
 */

    public function afterInsert(string $table, array $fields, $id): void
    {
        if ($this->onInsert) {
            ($this->onInsert)($table, $fields, $id);
        }
    }

/**
 * afterBulkInsert
 * @param string $table
 * @param array $rows
 * @param int $count
 * @return void
 */

    public function afterBulkInsert(string $table, array $rows, int $count): void
    {
        if ($this->onBulkInsert) {
            ($this->onBulkInsert)($table, $rows, $count);
        }
    }

/**
 * afterUpdate
 * @param string $table
 * @param array $fields
 * @param int $count
 * @return void
 */

    public function afterUpdate(string $table, array $fields, int $count): void
    {
        if ($this->onUpdate) {
            ($this->onUpdate)($table, $fields, $count);
        }
    }

/**
 * afterDelete
 * @param string $table
 * @param int $count
 * @return void
 */

    public function afterDelete(string $table, int $count): void
    {
        if ($this->onDelete) {
            ($this->onDelete)($table, $count);
        }
    }
}
