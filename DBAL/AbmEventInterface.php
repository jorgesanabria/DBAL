<?php
namespace DBAL;

interface AbmEventInterface extends MiddlewareInterface
{
    public function afterInsert(string $table, array $fields, $id): void;
    public function afterBulkInsert(string $table, array $rows, int $count): void;
    public function afterUpdate(string $table, array $fields, int $count): void;
    public function afterDelete(string $table, int $count): void;
}
