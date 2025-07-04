<?php
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;

/**
 * Clase/Interfaz EntityValidationInterface
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
