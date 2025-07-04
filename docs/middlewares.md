# Built-in Middlewares

DBAL ships with several optional middlewares that can be attached to a `Crud` instance.

## ActiveRecordMiddleware
Provides `ActiveRecord` objects with dynamic getters and setters. Use `$record->update()` to persist modified fields.

## EntityCastMiddleware
Casts query results into instances of a specified class and inspects its attribute
annotations to configure relations for eager loading. Attach the middleware and
register the class mapped to a table:

```php
$caster = (new DBAL\EntityCastMiddleware())
    ->register('users', UserEntity::class);

$crud = (new DBAL\Crud($pdo))->from('users');
$crud = $caster->attach($crud, 'users');
```

Relations defined with `#[HasOne]`, `#[HasMany]` or `#[BelongsTo]` on the class
properties will be available for lazy or eager loading via `with()`.

If the entity class uses `ActiveRecordTrait` the returned objects can be
updated directly and new instances can be inserted:

```php
class UserEntity {
    use DBAL\ActiveRecordTrait;
    public $id;
    public $name;
}

$crud = (new DBAL\Crud($pdo))->from('users');
$crud = $caster->attach($crud, 'users');

$user = iterator_to_array($crud->select())[0];
$user->name = 'Bob';
$user->update();

$another = new UserEntity();
$another->name = 'Alice';
$crud->insertObject($another);
```

Multiple entities can also be inserted at once with `bulkInsertObjects()`.

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

## LoggingMiddleware
Logs every executed SQL statement. The constructor accepts a PSR-3 logger or any
callable receiving the SQL string and bound values.

```php
use Psr\Log\NullLogger;

$logger = new NullLogger();
$crud = (new DBAL\Crud($pdo))
    ->from('users')
    ->withMiddleware(new DBAL\LoggingMiddleware($logger));
```

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

## CrudEventMiddleware
Allows executing callbacks after insert, bulk insert, update or delete operations.

## FirstLastMiddleware
Adds `first()`, `firstOrDefault()`, `last()` and `lastOrDefault()` to quickly fetch a single row.

## LinqMiddleware
Adds helper methods for quick queries:

- `any(...$filters)` returns `true` when at least one row matches.
- `none(...$filters)` returns `true` when no rows match.
- `all(...$filters)` returns `true` when every row matches or there are no rows.
- `notAll(...$filters)` returns `true` when some rows do not match.
- `count(...$filters)` returns the number of matching rows.
- `max($field)` returns the maximum value of the given field.
- `min($field)` returns the minimum value of the given field.
- `sum($field)` returns the sum of the values in the given field.
- `average($field)` returns the average value of the given field.
- `distinct($field)` returns an array with the distinct values of the field.

## RxMiddleware
Adds helpers inspired by RxJS:

- `map($crud, callable $fn, ...$fields)` applies a transformation to each row.
- `filter($crud, callable $fn, ...$fields)` yields only matching rows.
- `reduce($crud, callable $fn, $initial, ...$fields)` aggregates rows into a single value.
- `debounce($crud, $ms, ...$fields)` delays each yielded row by the given milliseconds.
- `catchError(callable $op, callable $handler)` executes `$op` and passes any error to `$handler`.
- `retry(callable $op, $times, $delayMs)` retries `$op` if it throws an exception.
- `merge(...$generators)` merges multiple generators.
- `concat(...$generators)` concatenates generators sequentially.

## EntityValidationMiddleware
Provides a fluent API to validate data and declare relations for eager or lazy loading.

## RelationLoaderMiddleware
Defines relations programmatically without PHP attributes. Use `table()` to pick
the table being configured and `hasOne()`, `hasMany()` or `belongsTo()` to
describe how rows relate. Each method returns the middleware for chaining.

- `table($name)` sets the table currently being configured.
- `hasOne($name, $table, $localKey, $foreignKey)` declares a one-to-one relation.
- `hasMany($name, $table, $localKey, $foreignKey)` declares a one-to-many relation.
- `belongsTo($name, $table, $localKey, $foreignKey)` references a parent row.

```php
$rel = (new DBAL\RelationLoaderMiddleware())
    ->table('users')
        ->hasOne('profile', 'profiles', 'id', 'user_id')
        ->hasMany('posts', 'posts', 'id', 'user_id')
    ->table('posts')
        ->belongsTo('user', 'users', 'user_id', 'id');

$crud = (new DBAL\Crud($pdo))
    ->from('users')
    ->withMiddleware($rel);

// Eager load
foreach ($crud->with('profile', 'posts')->select() as $user) {
    echo $user['profile']['bio'];
    foreach ($user['posts'] as $post) {
        echo $post['title'];
    }
}

// Lazy load
$user    = iterator_to_array($crud->where(['id' => 1])->select())[0];
$profile = $user['profile'];
$posts   = $user['posts'];
```

## GlobalFilterMiddleware
Appends extra filters automatically to every SELECT statement.

## SchemaMiddleware
Creates or alters tables through a fluent schema builder.

## QueryCounterMiddleware
Counts how many queries are executed by a `Crud`. Attach the middleware and call `getQueryCount()` to read the total.

```php
$counter = new DBAL\QueryCounterMiddleware();
$crud = (new DBAL\Crud($pdo))
    ->from('items')
    ->withMiddleware($counter);

$crud->select();
$queries = $counter->getQueryCount(); // 1
```


## DevelopmentErrorMiddleware
Installs an exception handler that displays a friendly HTML page when an
uncaught exception occurs. The constructor accepts:
- `console` (bool) writes a text version of the error to `STDERR`.
- `persistPath` (string) directory where generated files are stored.
- `theme` (string) either `light` or `dark`.
- `fontSize` (string) one of `small`, `medium` or `large`.

```php
$errors = new DBAL\DevelopmentErrorMiddleware([
    'console'     => true,
    'persistPath' => __DIR__.'/errors',
]);
$crud = (new DBAL\Crud($pdo))
    ->withMiddleware($errors);
```

If `persistPath` is configured the directory will contain timestamped folders
with the following files:
```
errors/
└── YYYYMMDD_HHMMSS/
    ├── error.html
    ├── style.css
    └── script.js
```

## QueryTimingMiddleware
Records how long each SQL statement takes to run. The collected timings can be
retrieved via `getTimings()`.

```php
$timing = new DBAL\QueryTimingMiddleware();
$crud = (new DBAL\Crud($pdo))
    ->from('items')
    ->withMiddleware($timing);

$crud->insert(['name' => 'A']);
iterator_to_array($crud->select());

foreach ($timing->getTimings() as $info) {
    echo $info['message'] . ' took ' . $info['time'] . "s\n";
}
```

Each middleware implements `MiddlewareInterface` and can be combined freely.

