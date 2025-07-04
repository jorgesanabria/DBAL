# DBAL

A lightweight Database Abstraction Layer for PHP.
## What's New
- Requires **PHP 8.1** and uses attributes for entity validation and relations
- ActiveRecord support with dynamic properties
- Caching middleware with pluggable storage
- Transaction and Unit of Work middlewares
- CRUD event hooks to listen for inserts, updates or deletes
- Improved documentation and error pages


## Features
- Fluent query builder for CRUD operations
- Dynamic filters via magic methods
- Streaming and [iterator-based results](docs/overview.md#resultiterator)
- Lazy and eager loading of relations
- Middleware system with caching, transactions, validation and more
- Schema builder and migration helpers
- Attribute based entity validation and relation definition
- Relation loader middleware for programmatic relations ([docs](docs/middlewares.md#relationloadermiddleware))
- First/Last and Linq helpers
- ActiveRecord objects for tracked updates
- Development error pages and global filters

## Installation

Install the library via [Composer](https://getcomposer.org/):

```bash
composer require jorgesanabria/dbal
```

## Basic Usage

```php
$pdo = new \PDO('mysql:host=localhost;dbname=test', 'user', 'pass');
$crud = (new DBAL\Crud($pdo))->from('users');
```
```sql
SELECT * FROM users;
```

### Insert records

```php
$id = $crud->insert([
    'name'  => 'John',
    'email' => 'john@example.com'
]);
```
```sql
INSERT INTO users (name, email) VALUES ('John', 'john@example.com');
```

### Bulk insert

```php
$count = $crud->bulkInsert([
    ['name' => 'Alice'],
    ['name' => 'Bob']
]);
```
```sql
INSERT INTO users (name) VALUES ('Alice'), ('Bob');
```

### Select with `where`

```php
$rows = $crud
    ->select('id', 'name')
    ->where(['id__gt' => 10]);

foreach ($rows as $row) {
    echo $row['name'];
}
```
```sql
SELECT id, name FROM users WHERE id > 10;
```

### Update and delete

```php
$crud->where(['id' => $id])->update(['name' => 'Peter']);

$crud->where(['id' => $id])->delete();
```
```sql
UPDATE users SET name = 'Peter' WHERE id = ?;
DELETE FROM users WHERE id = ?;
```

### Joins

```php
$result = $crud
    ->from('users u')
    ->leftJoin('profiles p', function ($on) {
        $on->{'u.id__eqf'}('p.user_id');
    })
    ->where(['p.active__eq' => 1])
    ->select('u.id', 'p.photo');
```
```sql
SELECT u.id, p.photo FROM users u LEFT JOIN profiles p ON u.id = p.user_id WHERE p.active = 1;
```

### Dynamic filters

```php
$crud->where(function ($q) {
    $q->name__startWith('Al')
       ->age__ge(21);
});
```
```sql
SELECT * FROM users WHERE name LIKE 'Al%%' AND age >= 21;
```

Dynamic filters can also be grouped with logical operators:

```php
$crud->where(function ($q) {
    $q->orGroup(function ($g) {
        $g->name__eq('Alice')->orNext()->name__eq('Bob');
    })->andGroup(function ($g) {
        $g->status__eq('active');
    });
});
```sql
SELECT * FROM users WHERE (name = 'Alice' OR name = 'Bob') AND status = 'active';
```
```

### Extending filters

Custom filters can be registered using `FilterNode::filter`:

```php
use DBAL\QueryBuilder\Node\FilterNode;

FilterNode::filter('startWith', function ($field, $value, $msg) {
    return $msg->insertAfter(sprintf('%s LIKE ?', $field))
               ->addValues([$value . '%']);
});

$crud->where(['name__startWith' => 'Al']);
```
```sql
SELECT * FROM users WHERE name LIKE 'Al%%';
```

For additional examples of extending filters and hiding complex conditions see
the [filters documentation](docs/filters.md).

### Grouping, ordering and limiting

Use `group()` or `groupBy()` to add a `GROUP BY` clause. The `having()` method
lets you filter aggregated results. Ordering can be controlled with `order()`,
`asc()` or `desc()`, while `limit()` and `offset()` constrain the amount of
rows returned.

```php
$rows = $crud
    ->group('status')
    ->having(['COUNT(*)__gt' => 1])
    ->desc('created_at')
    ->limit(10)
    ->offset(20)
    ->select('status', 'COUNT(*) AS total');
```
```sql
SELECT status, COUNT(*) AS total FROM users GROUP BY status HAVING COUNT(*) > 1 ORDER BY created_at DESC LIMIT 10 OFFSET 20;
```

### Mappers

```php
$crudWithMapper = $crud->map(function (array $row) {
    return (object) $row;
});

foreach ($crudWithMapper->select() as $row) {
    echo $row->name;
}
```
```sql
SELECT * FROM users;
```

### Grouping results

`ResultIterator` instances can group rows by a field name or a callback with `groupBy()`:

```php
$users = $crud->select();

$byStatus = $users->groupBy('status');

$byLetter = $users->groupBy(function ($row) {
    return $row['name'][0];
});
```
```sql
SELECT * FROM users;
```

### Pagination

Combine `limit()` and `offset()` to retrieve a specific page of results:

```php
$page    = 2;
$perPage = 20;

$rows = $crud
    ->limit($perPage)
    ->offset(($page - 1) * $perPage)
    ->select();
```
```sql
SELECT * FROM users LIMIT 20 OFFSET 20;
```

### Streaming results

`Crud::stream()` returns a generator that yields each row lazily. A callback can
be provided to process rows as they are produced. See the
[ResultIterator documentation](docs/overview.md#resultiterator) for details on
grouping results and exporting them to JSON.

```php
$generator = $crud->stream('id', 'name');

foreach ($generator as $row) {
    echo $row['name'];
}

$crud->stream(function ($row) {
    echo $row['name'];
}, 'id', 'name');
```

### Fetch all results

`Crud::fetchAll()` is a convenience method that returns an array with all rows
from a query.

```php
$rows = $crud->fetchAll('id', 'name');

foreach ($rows as $row) {
    echo $row['name'];
}
```

### Middlewares

Middlewares allow you to intercept query execution for tasks like logging or
validation. DBAL ships with a [LoggingMiddleware](docs/middlewares.md#loggingmiddleware)
that forwards executed SQL to any PSR-3 logger.

```php
$crud = (new DBAL\Crud($pdo))
    ->from('users')
    ->withMiddleware(function (DBAL\QueryBuilder\MessageInterface $msg) {
        error_log($msg->readMessage());
    });

$crud->insert(['name' => 'John']);
```

Alternatively, you can create a class that implements
`DBAL\MiddlewareInterface`:

```php
class MyMiddleware implements DBAL\MiddlewareInterface
{
    public function __invoke(DBAL\QueryBuilder\MessageInterface $msg): void
    {
        error_log($msg->readMessage());
    }
}

$crud = $crud->withMiddleware(new MyMiddleware());
```

You can register multiple middlewares and they will run before the SQL statement
is prepared and executed.

Middlewares can also be implemented as classes. Additional methods exposed by a
middleware are accessible through the `Crud` instance thanks to `__call`:

```php
use DBAL\MiddlewareInterface;
use DBAL\QueryBuilder\MessageInterface;

class LoggerMiddleware implements MiddlewareInterface
{
    public function __invoke(MessageInterface $msg): void
    {
        error_log($msg->readMessage());
    }

    public function greet($name)
    {
        return "Hello {$name}";
    }
}

$crud = (new DBAL\Crud($pdo))
    ->from('users')
    ->withMiddleware(new LoggerMiddleware());

echo $crud->greet('John'); // "Hello John"
```

### First/Last middleware

`FirstLastMiddleware` provides helper methods to retrieve the first or last row of a query.

```php
$fl = new DBAL\FirstLastMiddleware();
$crud = (new DBAL\Crud($pdo))
    ->from('users');
$crud = $fl->attach($crud);

$user = $crud->first();
$lastUser = $crud->last('id', 'name');
```

`first()` and `last()` throw a `RuntimeException` when no rows exist. `firstOrDefault()` and `lastOrDefault()` allow providing a default value.

### Linq middleware

`LinqMiddleware` exposes several helper methods for quick checks and aggregations.
See [LinqMiddleware docs](docs/middlewares.md#linqmiddleware) for the full list.

```php
$linq = new DBAL\LinqMiddleware();
$crud = (new DBAL\Crud($pdo))
    ->from('users')
    ->withMiddleware($linq);

$hasInactive = $crud->any(['active__eq' => 0]);
$noneInactive = $crud->none(['active__eq' => 1]);
$allActive = $crud->all(['active__eq' => 1]);
$someInactive = $crud->notAll(['active__eq' => 1]);

$total = $crud->count();
$highestId = $crud->max('id');
$lowestId = $crud->min('id');
$totalAge = $crud->sum('age');
```

### Entity validation middleware

`EntityValidationMiddleware` now reads PHP attributes from entity classes:

```php
use DBAL\Attributes\{Required, StringType, MaxLength, Email, HasOne};

class User {
    #[Required]
    #[StringType]
    #[MaxLength(50)]
    public $name;

    #[Required]
    #[Email]
    public $email;

    #[HasOne('profiles', 'id', 'user_id')]
    public $profile;
}

$validation = (new DBAL\EntityValidationMiddleware())
    ->register('users', User::class);

$crud = (new DBAL\Crud($pdo))
    ->from('users')
    ->withMiddleware($validation);
```

An `InvalidArgumentException` is thrown when validations fail. Declared
relations can be used by future lazy or eager loading features.

### Relationships and eager loading

Relationships are defined in the validation middleware using PHP 8.1 attributes.
Once set up, relations can be eagerly loaded via `with()` and are available lazily on demand.

```php
class User {
    #[HasOne('profiles', 'id', 'user_id')]
    public $profile;
}

class Profile {
    #[BelongsTo('users', 'user_id', 'id')]
    public $user;
}

$validation = (new DBAL\EntityValidationMiddleware())
    ->register('users', User::class)
    ->register('profiles', Profile::class);

$crud = (new DBAL\Crud($pdo))
    ->from('users')
    ->withMiddleware($validation);

// Eager load
$users = $crud->with('profile')->select();

foreach ($users as $user) {
    echo $user['profile']['photo'];
}

// Lazy load
$user = $crud->where(['id' => 1])->fetchAll()[0];
$profile = $user['profile'];
echo $profile['photo'];
```

### Relation loader middleware

If you prefer configuring relations programmatically, `RelationLoaderMiddleware`
offers a fluent API (see [RelationLoaderMiddleware docs](docs/middlewares.md#relationloadermiddleware)).

```php
$rel = (new DBAL\RelationLoaderMiddleware())
    ->table('users')
    ->hasOne('profile', 'profiles', 'id', 'user_id');

$crud = (new DBAL\Crud($pdo))
    ->from('users')
    ->withMiddleware($rel)
    ->with('profile');

$users = $crud->fetchAll();
```

Lazy loading works the same and the related records are fetched only when needed.

### Global filter middleware

`GlobalFilterMiddleware` can automatically append extra conditions to every SELECT statement. Filters can be declared globally or per table and work together with other middlewares.

```php
use DBAL\GlobalFilterMiddleware;

$mw = new GlobalFilterMiddleware([], [
    function ($m) {
        return stripos($m->readMessage(), 'WHERE') !== false
            ? $m->replace('WHERE', 'WHERE deleted = 0 AND')
            : $m->insertAfter('WHERE deleted = 0');
    }
]);

$crud = (new DBAL\Crud($pdo))
    ->from('users')
    ->withMiddleware($mw);
```

### OData middleware

`ODataMiddleware` converts an OData style query string into a DBAL query. The
middleware parses `$filter`, `$orderby`, `$top`, `$skip` and `$select`
parameters and applies them to a `Crud` instance. See
[`docs/odata.md`](docs/odata.md) for a detailed reference.

```php
$mw = new DBAL\ODataMiddleware();
$crud = $mw->attach((new DBAL\Crud($pdo))->from('books'));

$odata = '$filter=author_id eq 1 and price gt 10&$orderby=title desc&$top=5';
$rows  = $mw->query($odata);
```
The middleware can also be used to handle query strings directly from an HTTP
request. If the request URI is:

```
/books?$filter=category%20eq%20'fantasy'&$select=name,synopsis,author&$top=10
```

you can parse the query and apply the parameters as follows to get the `name`,
`synopsis` and `author` fields of up to ten fantasy books:

```php
$mw = new DBAL\ODataMiddleware();
$crud = $mw->attach((new DBAL\Crud($pdo))->from('books'));

$odata = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
$books = $mw->query($odata);

// The result will be an array with up to ten books where each item
// contains only the `name`, `synopsis` and `author` fields.
// Example:
// [
//     [
//         'name'     => 'The Hobbit',
//         'synopsis' => 'A hobbit goes on an adventure... ',
//         'author'   => 'J.R.R. Tolkien'
//     ],
//     ...
// ]
```
### Schema middleware

`SchemaMiddleware` provides a fluent API to create or modify tables via the `Crud` instance.

```php
$schema = new DBAL\SchemaMiddleware($pdo);
$crud = (new DBAL\Crud($pdo))
    ->withMiddleware($schema);

$crud->createTable('items')
    ->column('id', 'INTEGER PRIMARY KEY AUTOINCREMENT')
    ->column('name', 'TEXT')
    ->execute();

$crud->alterTable('items')
    ->addColumn('price', 'REAL')
    ->execute();
```

Both `createTable()` and `alterTable()` return an instance of
`SqlSchemaTableBuilder` used to define the table schema fluently.

### Development error middleware

`DevelopmentErrorMiddleware` installs an exception handler that displays a basic HTML page whenever an uncaught exception happens. The page supports light and dark themes and allows switching between small, medium and large fonts. When `console` is enabled a text version of the error is written to `STDERR`. If `persistPath` is provided, rendered pages are stored in timestamped folders so they can be reviewed later.

```php
$errors = new DBAL\DevelopmentErrorMiddleware([
    'console'     => true,
    'persistPath' => __DIR__.'/errors',
]);
$crud = (new DBAL\Crud($pdo))
    ->withMiddleware($errors);
```

### Cache middleware

`CacheMiddleware` caches the result of SELECT statements and clears the cache when data changes. It defaults to the in-memory `MemoryCacheStorage`, but you can use the provided `SqliteCacheStorage` or any custom `CacheStorageInterface` implementation.

### Active record

`ActiveRecordMiddleware` decorates rows with an object capable of tracking modified fields. Calling `$record->update()` only persists changes.

Example:

```php
$pdo = new \PDO('sqlite::memory:');
$pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
$pdo->exec('INSERT INTO users(name) VALUES ("Alice")');

$crud = (new DBAL\Crud($pdo))->from('users');
$ar   = (new DBAL\ActiveRecordMiddleware())->attach($crud);

$record = $ar->where(['id__eq' => 1])->fetchAll()[0];
$record->name = 'Alice2'; // or $record->set__name('Alice2');
$record->update(); // only changed fields are written
```

### Transaction and unit of work

`TransactionMiddleware` exposes helpers to start, commit or roll back transactions. `UnitOfWorkMiddleware` batches multiple operations and applies them atomically via `commit()`.  

### CRUD event middleware

`CrudEventMiddleware` lets you execute callbacks after inserts, bulk inserts, updates or deletes to implement custom hooks.

### Query timing middleware

`QueryTimingMiddleware` records how long each query takes. Attach it to a `Crud`
instance and inspect the timings afterwards.

```php
$timer = new DBAL\QueryTimingMiddleware();
$crud = (new DBAL\Crud($pdo))
    ->from('users')
    ->withMiddleware($timer);

$crud->insert(['name' => 'A']);
iterator_to_array($crud->select());

print_r($timer->getTimings());
```

## Real use cases

DBAL is primarily intended for building microservices, powering small scripts and supporting lightweight sites. The following examples illustrate possible ways to use the library in different domains:

- **Online book stores**: manage the catalogue and categories, monitor stock, handle orders or returns, and offer search or recommendation endpoints.
- **Cinema ticketing**: schedule screenings, reserve seats and sell tickets atomically.
- **Logistics microservices**: manage shipments, monitor packages across warehouses and reuse filters and schemas between services.

These example domains are merely illustrative—developers are free to decide where and how to apply the library.

DBAL integrates easily with minimal frameworks like Slim and Lumen or even plain PHP scripts. Additional examples, including a microblogging tutorial, can be found in the [docs](docs/) folder.

## Bookstore example

Below is a short walkthrough of common tasks for a fictional book store. It demonstrates how to insert, update, delete and search for books, map them to authors, validate input and run bulk operations inside transactions.

### Set up relations and validations

```php
$validation = (new DBAL\EntityValidationMiddleware())
    ->table('books')
        ->field('title')->required()->string()->maxLength(100)
        ->field('author_id')->required()->integer()
        ->relation('author', 'belongsTo', 'authors', 'author_id', 'id')
    ->table('authors')
        ->field('name')->required()->string()->maxLength(50)
        ->relation('books', 'hasMany', 'books', 'id', 'author_id');

$books = (new DBAL\Crud($pdo))
    ->from('books')
    ->withMiddleware($validation);
```

### Insert, update and delete

```php
$bookId = $books->insert([
    'title'     => 'Dune',
    'author_id' => 1,
]);

$books->where(['id' => $bookId])->update(['title' => 'Dune (Revised)']);
$books->where(['id' => $bookId])->delete();
```

### Filter and search

```php
$results = $books
    ->where(['title__like' => '%robot%']) // built-in LIKE filter
    ->order('ASC', ['title'])
    ->select('id', 'title');
```


### Filtering with dynamic methods

```php
$byAuthor = $books->where(function ($q) {
    $q->author_id__gt(1)->title__like('%dune%'); // built-in LIKE filter
})->select('id', 'title');
```
### Working with relations

```php
foreach ($books->with('author')->select() as $book) {
    echo $book['title'].' by '.$book['author']['name'];
}
```

See [LazyRelation documentation](docs/lazy-relations.md) for details on how related rows are loaded on demand and serialised.

### Bulk insert with transactions

```php
$tx = new DBAL\TransactionMiddleware();
$bulk = $books->withMiddleware($tx);

$tx->begin();
try {
    $bulk->bulkInsert([
        ['title' => 'Book A', 'author_id' => 2],
        ['title' => 'Book B', 'author_id' => 3],
    ]);
    $tx->commit();
} catch (Throwable $e) {
    $tx->rollback();
}
```

## Expanding DBAL

Middlewares are simple classes that implement `MiddlewareInterface`. Create your own to add behaviours such as auditing or soft deletes and attach them with `withMiddleware()`.

## Hook Helpers

Several convenience functions are available under the `DBAL\Hooks` namespace.
These helpers configure a `Crud` instance with common middlewares.

```php
use function DBAL\Hooks\useCrud;
use function DBAL\Hooks\useCache;
use function DBAL\Hooks\useTransaction;

$pdo = new PDO('sqlite::memory:');
$crud = useCrud($pdo, 'items');
$crud = useCache($crud);
[$crud, $tx] = useTransaction($crud);

$tx->begin();
$crud->insert(['name' => 'Example']);
$tx->commit();
```

Each `use*` function returns the configured `Crud` instance and, when
applicable, the middleware object so you can call helper methods like
`begin()` or `commit()`.


