# DBAL Documentation

DBAL is a lightweight Database Abstraction Layer written in PHP. It builds upon the PDO extension to offer a fluent and expressive way to manage SQL queries while remaining easy to integrate in any project. The library exposes a `Crud` class for select, insert, update and delete operations, an extensible middleware system, and helper utilities to simplify common tasks such as transaction handling or schema updates.

* **overview.md** – this file, quick introduction and benefits.
* **middlewares.md** – explanation of included middlewares and how to extend them.
* **integration.md** – examples of integrating DBAL with frameworks such as Slim and Lumen or plain PHP.
* **examples.md** – practical use cases for book stores, cinemas and logistic APIs.
* **hooks.md** – helper functions for quickly setting up middlewares.
* **filters.md** – expanding filters and hiding complex conditions.
* **twitter-tutorial.md** – building a microblogging platform example.

- **Simple query builder**: compose SQL statements through a chainable API.
- **Dynamic filters**: use magic methods or callbacks to create complex filtering logic.
- **Iterator or generator based results**: process rows lazily or load them eagerly as needed.
- **Relation loading**: define `hasOne`, `hasMany` and other relations and access related data with minimal code.
- **Powerful middlewares**: add caching, transaction support, validation or active record behaviour with plug‑and‑play components.
- **[Schema builder](schema-builder.md)**: create or alter tables programmatically without writing SQL strings.
- **Table specific helpers**: use custom middlewares to reuse filters on particular tables via the fluent API.
- **Friendly error pages**: during development a middleware can render detailed error screens.

DBAL aims to be framework‑agnostic and has no dependencies beyond PDO. It works equally well in small scripts or full applications.

## Documentation Map

- **`overview.md`** – this document. It introduces the library, outlines the main features and explains where to find more information.
- **`core.md`** – overview of the main classes and how the query builder is structured.
- **`node-architecture.md`** – detailed explanation of the query builder tree and how to replace nodes.
- **`middlewares.md`** – descriptions of the built‑in middlewares and how to create custom ones.
- **`odata.md`** – using `ODataMiddleware` to translate query strings into filters, ordering and pagination.
- **`integration.md`** – integration examples for Slim, Lumen and plain PHP usage.
- **`examples.md`** – practical scenarios such as managing a book store, handling cinema tickets or implementing a logistics API.
- **`lazy-relations.md`** – details the `LazyRelation` helper used for on-demand loading of related rows.
- **`filters.md`** – extending filters and simplifying queries.
- **`engines.md`** – supported database platforms and how to create your own.
- **`custom-nodes.md`** – examples of creating nodes for unsupported SQL features.
- **`schema-builder.md`** – using `SchemaMiddleware` to create, modify and migrate tables.

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

## ResultIterator

Calling `select()` returns a `ResultIterator` object. The iterator executes the query on the first iteration and keeps the fetched rows so it can be traversed multiple times. Any mappers declared with `map()` are applied to each row as it is returned.

### groupBy()

Use `groupBy()` to organise rows by a field name or with a callback that generates the grouping key:

```php
$users  = $crud->select();
$byStatus = $users->groupBy('status');
$byLetter = $users->groupBy(fn ($row) => $row['name'][0]);
```

### Iteration behaviour

Each loop over a `ResultIterator` works on the already fetched rows without executing the query again. This allows multiple traversals or converting the iterator to an array safely.

### jsonSerialize()

Because the iterator implements `JsonSerializable` it can be passed directly to `json_encode()`. The method rewinds the iterator and returns all rows as an array.

```php
$users   = $crud->select();
$grouped = $users->groupBy('status');

file_put_contents('users.json', json_encode($users));
```
