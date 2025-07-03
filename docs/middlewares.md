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

## AbmEventMiddleware
Allows executing callbacks after insert, bulk insert, update or delete operations.

## FirstLastMiddleware
Adds `first()`, `firstOrDefault()`, `last()` and `lastOrDefault()` to quickly fetch a single row.

## LinqMiddleware
Adds `any()` and `all()` methods for boolean existence checks.

## EntityValidationMiddleware
Provides a fluent API to validate data and declare relations for eager or lazy loading.

## GlobalFilterMiddleware
Appends extra filters automatically to every SELECT statement.

## SchemaMiddleware
Creates or alters tables through a fluent schema builder.

## DevelopmentErrorMiddleware
Shows a detailed HTML page when an uncaught exception happens during development.

Each middleware implements `MiddlewareInterface` and can be combined freely.

