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

    public function createTable(Crud $crud, string $table): SqlSchemaTableBuilder
    {
        return new SqlSchemaTableBuilder($this->pdo, $table, true);
    }

    public function alterTable(Crud $crud, string $table): SqlSchemaTableBuilder
    {
        return new SqlSchemaTableBuilder($this->pdo, $table, false);
    }
}
