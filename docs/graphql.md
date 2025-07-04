# GraphQL Middleware

`GraphQLMiddleware` executes GraphQL queries and mutations using a `Crud` instance. The middleware builds a simple schema for the primary table exposing a `read` query and `insert`, `update` and `delete` mutations.

## Usage

```php
$mw   = new DBAL\GraphQLMiddleware();
$crud = $mw->attach((new DBAL\Crud($pdo))->from('books'));

$result = $mw->handle('{ read { id, title } }');
```

Filters and record data are passed as associative arrays using the `JSON` scalar type. Dynamic filter operators from [`docs/filters.md`](filters.md) are supported.

```php
$mw->handle('mutation { insert(data: {title: "New"}) }');
$mw->handle('mutation { update(id: 1, data: {title: "Updated"}) }');
$mw->handle('mutation { delete(id: 1) }');
```

The `handle()` method returns the execution result as an array compatible with `GraphQL\GraphQL::executeQuery()`.
