# DBAL

A lightweight Database Abstraction Layer for PHP.

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

### Insert records

```php
$id = $crud->insert([
    'name'  => 'John',
    'email' => 'john@example.com'
]);
```

### Bulk insert

```php
$count = $crud->bulkInsert([
    ['name' => 'Alice'],
    ['name' => 'Bob']
]);
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

### Update and delete

```php
$crud->where(['id' => $id])->update(['name' => 'Peter']);

$crud->where(['id' => $id])->delete();
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

### Dynamic filters

```php
$crud->where(function ($q) {
    $q->name__startWith('Al')
       ->age__ge(21);
});
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

### Grouping, ordering and limiting

```php
$rows = $crud
    ->groupBy('status')
    ->order('DESC', ['created_at'])
    ->limit(10)
    ->offset(20)
    ->select();
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

### Grouping results

`ResultIterator` instances can group rows by a field name or a callback with `groupBy()`:

```php
$users = $crud->select();

$byStatus = $users->groupBy('status');

$byLetter = $users->groupBy(function ($row) {
    return $row['name'][0];
});
```

### Streaming results

`Crud::stream()` returns a generator that yields each row lazily. A callback can
be provided to process rows as they are produced.

```php
$generator = $crud->stream('id', 'name');

foreach ($generator as $row) {
    echo $row['name'];
}

$crud->stream(function ($row) {
    echo $row['name'];
}, 'id', 'name');
```

### Middlewares

Middlewares allow you to intercept query execution for tasks like logging or
validation.

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

`LinqMiddleware` exposes helper methods to query for the existence of records.

```php
$linq = new DBAL\LinqMiddleware();
$crud = (new DBAL\Crud($pdo))
    ->from('users')
    ->withMiddleware($linq);

$hasInactive = $crud->any(['active__eq' => 0]);
$allActive = $crud->all(['active__eq' => 1]);
```

### Entity validation middleware

`EntityValidationMiddleware` provides a fluent API to validate data before it is
inserted or updated. Validations are defined per table and field:

```php
$validation = (new DBAL\EntityValidationMiddleware())
    ->table('users')
        ->field('name')->required()->string()->maxLength(50)
        ->field('email')->required()->email()
        ->relation('profile')
            ->hasOne('profiles')
            ->on('users.id', '=', 'profiles.user_id');

$crud = (new DBAL\Crud($pdo))
    ->from('users')
    ->withMiddleware($validation);
```

An `InvalidArgumentException` is thrown when validations fail. Declared
relations can be used by future lazy or eager loading features.

### Relationships and eager loading

Relationships are defined in the validation middleware. Once set up, relations
can be eagerly loaded via `with()` and are available lazily on demand.

```php
$validation = (new DBAL\EntityValidationMiddleware())
    ->table('users')
        ->relation('profile', 'hasOne', 'profiles', 'id', 'user_id')
    ->table('profiles')
        ->relation('user', 'belongsTo', 'users', 'user_id', 'id');

$crud = (new DBAL\Crud($pdo))
    ->from('users')
    ->withMiddleware($validation);

// Eager load
$users = $crud->with('profile')->select();

foreach ($users as $user) {
    echo $user['profile']['photo'];
}

// Lazy load
$user = iterator_to_array($crud->where(['id' => 1])->select())[0];
$profile = $user['profile'];
echo $profile['photo'];
```

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
### Schema middleware

`SchemaMiddleware` provides a fluent API to create or modify tables via the `Crud` instance.

```php
$schema = new DBAL\SchemaMiddleware($pdo);
$crud = (new DBAL\Crud($pdo))
    ->withMiddleware($schema);

$crud->createTable('items')
    ->column('id INTEGER PRIMARY KEY AUTOINCREMENT')
    ->column('name TEXT')
    ->execute();

$crud->alterTable('items')
    ->addColumn('price REAL')
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
