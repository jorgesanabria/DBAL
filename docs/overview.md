# DBAL Documentation

DBAL is a lightweight Database Abstraction Layer written in PHP. It builds upon the PDO extension to offer a fluent and expressive way to manage SQL queries while remaining easy to integrate in any project. The library exposes a `Crud` class for select, insert, update and delete operations, an extensible middleware system, and helper utilities to simplify common tasks such as transaction handling or schema updates.

## Why DBAL?

- **Simple query builder**: compose SQL statements through a chainable API.
- **Dynamic filters**: use magic methods or callbacks to create complex filtering logic.
- **Iterator or generator based results**: process rows lazily or load them eagerly as needed.
- **Relation loading**: define `hasOne`, `hasMany` and other relations and access related data with minimal code.
- **Powerful middlewares**: add caching, transaction support, validation or active record behaviour with plug‑and‑play components.
- **Schema builder**: create or alter tables programmatically without writing SQL strings.
- **Friendly error pages**: during development a middleware can render detailed error screens.

DBAL aims to be framework‑agnostic and has no dependencies beyond PDO. It works equally well in small scripts or full applications.

## Documentation Map

The documentation in this folder is organised into several topics:

- **`overview.md`** – this document. It introduces the library, outlines the main features and explains where to find more information.
- **`middlewares.md`** – descriptions of the built‑in middlewares and how to create custom ones.
- **`odata.md`** – using `ODataMiddleware` to translate query strings into filters, ordering and pagination.
- **`integration.md`** – integration examples for Slim, Lumen and plain PHP usage.
- **`examples.md`** – practical scenarios such as managing a book store, handling cinema tickets or implementing a logistics API.

Each file can be read in isolation, but together they provide a comprehensive guide to DBAL.

## Getting Started

Installing the package via Composer is straightforward:

```bash
composer require jorgesanabria/dbal
```

Create a `Crud` instance with your PDO connection and start issuing commands:

```php
$pdo  = new \PDO('sqlite:app.db');
$crud = (new DBAL\Crud($pdo))->from('users');

foreach ($crud->select('id', 'name') as $user) {
    echo $user['name'];
}
```

From here you can chain methods to insert or update records, join other tables, apply filters and attach any middleware your project requires. The rest of the documentation explores these features in more depth.
