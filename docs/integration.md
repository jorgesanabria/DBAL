# Using DBAL with Other Frameworks

DBAL is framework agnostic and can be used alongside popular minimalistic frameworks or in plain PHP scripts.

## Slim Framework
```php
use Psr\Container\ContainerInterface;
use DBAL\Crud;

return function (ContainerInterface $c) {
    $pdo = $c->get('pdo');
    return (new Crud($pdo))->from('books');
};
```

## Lumen
```php
$router->get('/books', function () {
    $pdo = app('db')->connection()->getPdo();
    $crud = (new DBAL\Crud($pdo))->from('books');
    return response()->json(iterator_to_array($crud->select()));
});
```

## Plain PHP
```php
$pdo = new PDO('sqlite:app.db');
$books = (new DBAL\Crud($pdo))->from('books')->select();
foreach ($books as $book) {
    echo $book['title'];
}
```

DBAL fits naturally into any existing project thanks to its single dependency on PDO.


