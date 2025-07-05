# Using DBAL with Other Frameworks

DBAL is framework agnostic and can be used alongside popular minimalistic frameworks or in plain PHP scripts.

## Slim Framework
```php
use Psr\Container\ContainerInterface;
use DBAL\Crud;

return fn (ContainerInterface $c) =>
    (new Crud($c->get('pdo')))->from('books');
```

## Lumen
```php
$router->get('/books', fn () =>
    response()->json(
        iterator_to_array(
            (new DBAL\Crud(app('db')->connection()->getPdo()))
                ->from('books')
                ->select()
        )
    )
);
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


