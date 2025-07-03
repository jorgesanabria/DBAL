<?php
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;

interface EntityValidationInterface extends MiddlewareInterface
{
    public function beforeInsert(string $table, array $fields): void;
    public function beforeUpdate(string $table, array $fields): void;
}
