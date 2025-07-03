<?php
namespace DBAL;

use PDO;
use DBAL\QueryBuilder\MessageInterface;

class SchemaMiddleware implements MiddlewareInterface, CrudAwareMiddlewareInterface
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(MessageInterface $msg): void
    {
        // no-op
    }

    public function createTable(Crud $crud, string $table): SchemaTableBuilder
    {
        return new SchemaTableBuilder($this->pdo, $table, true);
    }

    public function alterTable(Crud $crud, string $table): SchemaTableBuilder
    {
        return new SchemaTableBuilder($this->pdo, $table, false);
    }
}
