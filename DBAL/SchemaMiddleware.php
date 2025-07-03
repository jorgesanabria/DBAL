<?php
namespace DBAL;

use PDO;
use DBAL\QueryBuilder\MessageInterface;

/**
 * Clase/Interfaz SchemaMiddleware
 */
class SchemaMiddleware implements MiddlewareInterface, CrudAwareMiddlewareInterface
{
/** @var mixed */
    private $pdo;

/**
 * __construct
 * @param PDO $pdo
 * @return void
 */

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
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
