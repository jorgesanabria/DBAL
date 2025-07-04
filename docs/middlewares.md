# Built-in Middlewares

DBAL ships with several optional middlewares that can be attached to a `Crud` instance.

## ActiveRecordMiddleware
Provides `ActiveRecord` objects with dynamic getters and setters. Use `$record->update()` to persist modified fields.

## CacheMiddleware
Caches SELECT query results using an in-memory or custom storage. Results are invalidated when insert, update or delete statements run.

## TransactionMiddleware
Wraps operations inside database transactions and exposes `begin()`, `commit()` and `rollback()` helpers.

## UnitOfWorkMiddleware
Registers new, dirty and deleted entities and persists them in a single transaction via `commit()`.

The typical workflow is:

1. Create a `TransactionMiddleware` and pass it to `UnitOfWorkMiddleware`.
2. Attach both middlewares to a `Crud` instance.
3. Register pending inserts, updates or deletes.
4. Call `commit()` to apply every operation atomically. The batch is executed inside the `TransactionMiddleware` and the tracked changes are cleared afterwards.

```php
$tx  = new DBAL\TransactionMiddleware($pdo);
$uow = new DBAL\UnitOfWorkMiddleware($tx);

$crud = (new DBAL\Crud($pdo))
    ->from('items')
    ->withMiddleware($uow)
    ->withMiddleware($tx);

$crud->registerNew('items', ['name' => 'A']);
$crud->registerDirty('items', ['name' => 'B'], ['id' => 1]);
$crud->registerDelete('items', ['id' => 2]);

$crud->commit();
```

## AbmEventMiddleware
Allows executing callbacks after insert, bulk insert, update or delete operations.

## FirstLastMiddleware
Adds `first()`, `firstOrDefault()`, `last()` and `lastOrDefault()` to quickly fetch a single row.

## LinqMiddleware
Adds `any()`, `all()`, `count()`, `max()`, `min()` and `sum()` methods for quick queries.

## EntityValidationMiddleware
Provides a fluent API to validate data and declare relations for eager or lazy loading.

## GlobalFilterMiddleware
Appends extra filters automatically to every SELECT statement.

## SchemaMiddleware
Creates or alters tables through a fluent schema builder.

## DevelopmentErrorMiddleware
Shows a detailed HTML page when an uncaught exception happens during development.

Each middleware implements `MiddlewareInterface` and can be combined freely.

