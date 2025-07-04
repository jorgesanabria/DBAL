# Built-in Middlewares

DBAL ships with several optional middlewares that can be attached to a `Crud` instance.

## ActiveRecordMiddleware
Provides `ActiveRecord` objects with dynamic getters and setters. Use `$record->update()` to persist modified fields.

## CacheMiddleware

`CacheMiddleware` stores the rows returned by SELECT statements. By default it
relies on an in-memory `MemoryCacheStorage` that lives for the duration of the
request. Whenever an insert, update or delete is executed through the attached
`Crud` instance the cache is flushed to keep results consistent.

If you need persistence across requests you can provide a different storage
backend. DBAL includes a `SqliteCacheStorage` adapter:

```php
$cache = new DBAL\CacheMiddleware(
    new DBAL\SqliteCacheStorage(__DIR__ . '/cache.sqlite')
);
$crud = (new DBAL\Crud($pdo))
    ->from('users')
    ->withMiddleware($cache);
```

Custom storages can be implemented by fulfilling the
`CacheStorageInterface` contract:

```php
use DBAL\CacheStorageInterface;

class ArrayCache implements CacheStorageInterface
{
    private array $data = [];

    public function get(string $key)
    {
        return $this->data[$key] ?? null;
    }

    public function set(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    public function delete(string $key = null): void
    {
        if ($key === null) {
            $this->data = [];
        } else {
            unset($this->data[$key]);
        }
    }
}

$crud = $crud->withMiddleware(new DBAL\CacheMiddleware(new ArrayCache()));
```

## TransactionMiddleware
Wraps operations inside database transactions and exposes `begin()`, `commit()` and `rollback()` helpers.

## UnitOfWorkMiddleware
Registers new, dirty and deleted entities and persists them in a single transaction via `commit()`.

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

