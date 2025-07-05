<?php
declare(strict_types=1);
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;

interface EntityCastInterface extends MiddlewareInterface
{
    public function castInsert(string $table, array $fields): array;
    public function castUpdate(string $table, array $fields): array;
}
