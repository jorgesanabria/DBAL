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
backend. DBAL includes adapters for SQLite, Redis and Memcached:

```php
$cache = new DBAL\CacheMiddleware(
    new DBAL\SqliteCacheStorage(__DIR__ . '/cache.sqlite')
);
// or
$cache = new DBAL\CacheMiddleware(
    new DBAL\RedisCacheStorage($redis)
);
// or
$cache = new DBAL\CacheMiddleware(
    new DBAL\MemcachedCacheStorage($memcached)
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

## QueueEventMiddleware
Publishes CRUD events to an external message queue like Kafka. Construct it with a callable publisher and the target topic.

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

## TypeSecurityMiddleware
Casts inserted and updated values according to validation attributes and hides
properties marked with `#[Hidden]`. Register an entity class and attach the
middleware to filter out sensitive fields from query results:

```php
#[DBAL\Attributes\Table('users')]
class User {
    #[DBAL\Attributes\StringType]
    public $name;
    #[DBAL\Attributes\Hidden]
    public $password;
}

$ts = (new DBAL\TypeSecurityMiddleware())
    ->register(User::class);
$crud = (new DBAL\Crud($pdo))->from('users');
$crud = $ts->attach($crud, 'users');
```

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

## Table specific middlewares
Sometimes a condition should apply only to queries on a given table. Rather than
modifying raw SQL strings, expose helper methods from a middleware that returns
a new `Crud` instance with extra clauses added through the fluent API.

```php
use DBAL\Crud;
use DBAL\MiddlewareInterface;
use DBAL\CrudAwareMiddlewareInterface;
use DBAL\QueryBuilder\MessageInterface;

class SoftDeleteMiddleware implements MiddlewareInterface, CrudAwareMiddlewareInterface
{
    public function __invoke(MessageInterface $msg): void
    {
        // no-op
    }

    // Add `WHERE <table>.deleted_at IS NULL` to the query
    public function withoutDeleted(Crud $crud, string $table): Crud
    {
        return $crud->where(["{$table}.deleted_at__isnull" => null]);
    }
}

$softDelete = new SoftDeleteMiddleware();
$crud = (new DBAL\Crud($pdo))
    ->from('users')
    ->withMiddleware($softDelete);

$rows = $crud->withoutDeleted('users')->select('id', 'name');
```

This keeps the modification declarative and lets the middleware work with others
like `CacheMiddleware` or `GlobalFilterMiddleware`.

Each middleware implements `MiddlewareInterface` and can be combined freely.

## Adding dynamic methods

Custom middlewares may also expose helper functions that become available
through `Crud::__call()`. Implement `CrudAwareMiddlewareInterface` alongside
`MiddlewareInterface` and define any extra methods your application requires.

```php
use DBAL\Crud;
use DBAL\CrudAwareMiddlewareInterface;
use DBAL\MiddlewareInterface;
use DBAL\QueryBuilder\Node\FieldNode;
use DBAL\QueryBuilder\Node\OrderNode;
use DBAL\QueryBuilder\MessageInterface;

class DefaultSortMiddleware implements MiddlewareInterface, CrudAwareMiddlewareInterface
{
    public function __invoke(MessageInterface $msg): void
    {
        // no-op
    }

    // This method is called dynamically through Crud::__call()
    public function applyDefaultSort(Crud $crud): Crud
    {
        $crud->removeChild('order');
        $crud->appendChild(new OrderNode());
        $crud->getChild('order')->appendChild(new FieldNode('created_at DESC'));
        return $crud;
    }
}

$crud = (new DBAL\Crud($pdo))
    ->from('users')
    ->withMiddleware(new DefaultSortMiddleware());

// applyDefaultSort() is resolved on the middleware
$rows = $crud->applyDefaultSort()->select('id', 'name');
```

When the method is invoked, the current `Crud` instance is passed as the first
argument automatically. This pattern allows middlewares to register domain
specific helpers and keep your queries concise.

