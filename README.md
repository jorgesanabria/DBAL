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
    ->group('status')
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

### Entity validation middleware

`EntityValidationMiddleware` provides a fluent API to validate data before it is
inserted or updated. Validations are defined per table and field:

```php
$validation = (new DBAL\EntityValidationMiddleware())
    ->table('users')
        ->field('name')->required()->string()->maxLength(50)
        ->field('email')->required()->email()
    ->relation('profile', 'hasOne', 'profiles', 'id', 'user_id');

$crud = (new DBAL\Crud($pdo))
    ->from('users')
    ->withMiddleware($validation);
```

An `InvalidArgumentException` is thrown when validations fail. Declared
relations can be used by future lazy or eager loading features.

### Loading relations

`RelationLoaderMiddleware` lets you define relationships and load them eagerly
or lazily.

```php
$relations = (new DBAL\RelationLoaderMiddleware())
    ->table('users')
        ->hasOne('profile', 'profiles', 'id', 'user_id');

$crud = (new DBAL\Crud($pdo))
    ->from('users')
    ->withMiddleware($relations);

// Eager load using JOIN
$rows = iterator_to_array($crud->with('profile')->select());

// Lazy load on demand
$row = iterator_to_array($crud->select())[0];
$profile = $row['profile']->get();
```

