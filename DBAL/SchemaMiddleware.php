<?php
declare(strict_types=1);
namespace DBAL;

use PDO;
use DBAL\QueryBuilder\MessageInterface;

/**
 * Middleware exposing schema management helpers to Crud instances.
 */
class SchemaMiddleware implements MiddlewareInterface, CrudAwareMiddlewareInterface
{

/**
 * __construct
 * @param PDO $pdo
 * @return void
 */

    public function __construct(private PDO $pdo)
    {
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
 * createTable
 * @param Crud $crud
 * @param string $table
 * @return SqlSchemaTableBuilder
 */

    public function createTable(Crud $crud, string $table): SqlSchemaTableBuilder
    {
        return new SqlSchemaTableBuilder($this->pdo, $table, true);
    }

/**
 * alterTable
 * @param Crud $crud
 * @param string $table
 * @return SqlSchemaTableBuilder
 */

    public function alterTable(Crud $crud, string $table): SqlSchemaTableBuilder
    {
        return new SqlSchemaTableBuilder($this->pdo, $table, false);
    }
}
