<?php
declare(strict_types=1);
namespace DBAL;

/**
 * Event hooks triggered after CRUD operations.
 */
interface CrudEventInterface extends MiddlewareInterface
{
/**
 * afterInsert
 * @param string $table
 * @param array $fields
 * @param mixed $id
 * @return void
 */

    public function afterInsert(string $table, array $fields, $id): void;
/**
 * afterBulkInsert
 * @param string $table
 * @param array $rows
 * @param int $count
 * @return void
 */

    public function afterBulkInsert(string $table, array $rows, int $count): void;
/**
 * afterUpdate
 * @param string $table
 * @param array $fields
 * @param int $count
 * @return void
 */

    public function afterUpdate(string $table, array $fields, int $count): void;
/**
 * afterDelete
 * @param string $table
 * @param int $count
 * @return void
 */

    public function afterDelete(string $table, int $count): void;
}
