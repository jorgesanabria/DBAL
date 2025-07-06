# Schema Builder

`SchemaMiddleware` exposes methods to create and modify tables through the `Crud` instance.
It keeps migrations in PHP without the need to craft raw SQL strings.

## Creating tables

```php
$schema = new DBAL\SchemaMiddleware($pdo);
$crud = (new DBAL\Crud($pdo))
    ->withMiddleware($schema);

$crud->createTable('items')
    ->column('id', 'INTEGER PRIMARY KEY AUTOINCREMENT')
    ->column('name', 'TEXT')
    ->execute();
```

Calling `createTable()` returns a `SqlSchemaTableBuilder`. Each `column()` call
adds a fragment to the `CREATE TABLE` statement. `execute()` runs the generated
SQL using the PDO connection.

## Altering tables

Use `alterTable()` to modify existing tables. The builder provides helpers like
`addColumn()` and `dropColumn()`:

```php
$crud->alterTable('items')
    ->addColumn('price', 'REAL')
    ->dropColumn('old_price')
    ->execute();
```

The statements are executed when `execute()` is called.

## Migrations

Because builders simply append SQL fragments, you can compose incremental
migrations in PHP:

```php
$schema = new DBAL\SchemaMiddleware($pdo);
$crud   = (new DBAL\Crud($pdo))->withMiddleware($schema);

// version 1
$crud->createTable('migrations')
    ->column('id INTEGER PRIMARY KEY AUTOINCREMENT')
    ->column('name TEXT')
    ->execute();

// version 2
$crud->alterTable('migrations')
    ->addColumn('ran_at', 'TEXT')
    ->execute();
```

Wrap these operations in transactions if your database supports them to ensure
consistency.
