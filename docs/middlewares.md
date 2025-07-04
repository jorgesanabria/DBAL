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
Adds `any()`, `all()`, `count()`, `max()`, `min()` and `sum()` methods for quick queries.

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

Each middleware implements `MiddlewareInterface` and can be combined freely.

