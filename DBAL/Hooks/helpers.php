<?php
namespace DBAL\Hooks;

use PDO;
use DBAL\Crud;
use DBAL\CacheMiddleware;
use DBAL\CacheStorageInterface;
use DBAL\TransactionMiddleware;
use DBAL\UnitOfWorkMiddleware;
use DBAL\ActiveRecordMiddleware;
use DBAL\FirstLastMiddleware;
use DBAL\LinqMiddleware;
use DBAL\DevelopmentErrorMiddleware;
use DBAL\GlobalFilterMiddleware;
use DBAL\SchemaMiddleware;
use DBAL\EntityValidationMiddleware;
use DBAL\AbmEventMiddleware;
use DBAL\ODataMiddleware;

/**
 * Create a Crud instance bound to a table.
 */
function useCrud(PDO $pdo, string $table): Crud
{
    return (new Crud($pdo))->from($table);
}

/**
 * Attach caching to the given Crud instance.
 */
function useCache(Crud $crud, CacheStorageInterface $storage = null): Crud
{
    $mw = new CacheMiddleware($storage);
    return $crud->withMiddleware($mw);
}

/**
 * Attach transaction middleware and return it along with the Crud.
 *
 * @return array{Crud, TransactionMiddleware}
 */
function useTransaction(Crud $crud): array
{
    $pdo = (function () {
        return $this->connection;
    })->call($crud);
    $tx = new TransactionMiddleware($pdo);
    $crud = $crud->withMiddleware($tx);
    return [$crud, $tx];
}

/**
 * Attach unit of work support. A TransactionMiddleware is created internally.
 *
 * @return array{Crud, UnitOfWorkMiddleware}
 */
function useUnitOfWork(Crud $crud): array
{
    [$crud, $tx] = useTransaction($crud);
    $uow = new UnitOfWorkMiddleware($tx);
    $crud = $crud->withMiddleware($uow);
    return [$crud, $uow];
}

/**
 * Decorate result rows with ActiveRecord objects.
 */
function useActiveRecord(Crud $crud): Crud
{
    $mw = new ActiveRecordMiddleware();
    return $mw->attach($crud);
}

/**
 * Add First/Last helpers to the Crud instance.
 */
function useFirstLast(Crud $crud): Crud
{
    $mw = new FirstLastMiddleware();
    return $mw->attach($crud);
}

/**
 * Add LINQ-like helper methods to the Crud instance.
 */
function useLinq(Crud $crud): Crud
{
    $mw = new LinqMiddleware();
    return $crud->withMiddleware($mw);
}

/**
 * Register entity validation middleware.
 */
function useValidation(Crud $crud, EntityValidationMiddleware $validator): Crud
{
    return $crud->withMiddleware($validator);
}

/**
 * Apply global or table-specific filters to SELECT statements.
 */
function useGlobalFilter(Crud $crud, array $tableFilters = [], array $globalFilters = []): Crud
{
    $mw = new GlobalFilterMiddleware($tableFilters, $globalFilters);
    return $crud->withMiddleware($mw);
}

/**
 * Attach schema helper middleware and return it.
 *
 * @return array{Crud, SchemaMiddleware}
 */
function useSchema(Crud $crud): array
{
    $pdo = (function () {
        return $this->connection;
    })->call($crud);
    $mw = new SchemaMiddleware($pdo);
    $crud = $crud->withMiddleware($mw);
    return [$crud, $mw];
}

/**
 * Register middleware to display detailed errors during development.
 */
function useDevelopmentErrors(Crud $crud, array $options = []): Crud
{
    $mw = new DevelopmentErrorMiddleware($options);
    return $crud->withMiddleware($mw);
}

/**
 * Register callbacks triggered after insert, update or delete operations.
 *
 * @return array{Crud, AbmEventMiddleware}
 */
function useAbmEvents(
    Crud $crud,
    ?callable $onInsert = null,
    ?callable $onUpdate = null,
    ?callable $onDelete = null,
    ?callable $onBulkInsert = null
): array {
    $mw = new AbmEventMiddleware($onInsert, $onUpdate, $onDelete, $onBulkInsert);
    $crud = $crud->withMiddleware($mw);
    return [$crud, $mw];
}

/**
 * Attach the OData middleware for parsing OData query strings.
 *
 * @return array{Crud, ODataMiddleware}
 */
function useOData(Crud $crud): array
{
    $mw = new ODataMiddleware();
    $crud = $crud->withMiddleware($mw);
    return [$crud, $mw];
}
