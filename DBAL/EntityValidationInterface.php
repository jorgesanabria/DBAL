<?php
declare(strict_types=1);
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;

/**
 * Middleware interface for validating data before it is persisted.
 */
interface EntityValidationInterface extends MiddlewareInterface
{
/**
 * beforeInsert
 * @param string $table
 * @param array $fields
 * @return void
 */

    public function beforeInsert(string $table, array $fields): void;
/**
 * beforeUpdate
 * @param string $table
 * @param array $fields
 * @return void
 */

    public function beforeUpdate(string $table, array $fields): void;
}
