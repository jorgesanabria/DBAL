# OData Middleware

`ODataMiddleware` converts OData style query strings into calls on a `Crud` instance. It understands `$filter`, `$orderby`, `$top`, `$skip` and `$select` parameters so that HTTP query strings can directly drive database queries.

## Purpose

The middleware bridges external APIs that expose OData inspired parameters with DBAL. It parses the query string, builds the equivalent filters and ordering clauses and keeps track of the fields requested via `$select`.

## API

### apply()

`apply(Crud $crud, string $query): Crud` parses the given query string and returns a cloned `Crud` object with the corresponding limit, offset, ordering and filters applied.

### query()

`query(string $odata): array` applies the query to the `Crud` instance attached through `attach()` and returns the resulting rows as an array.

### getFields()

`getFields(): array` returns the list of fields extracted from `$select`. When no `$select` parameter is present an empty array is returned.

## Examples

### Filtering and ordering
```php
$mw   = new DBAL\ODataMiddleware();
$crud = $mw->attach((new DBAL\Crud($pdo))->from('books'));

$query = '$filter=author_id eq 1 and price gt 10&$orderby=title desc';
$filtered = $mw->apply($crud, $query);
$rows = iterator_to_array($filtered->select(...$mw->getFields()));
```

### Pagination and field selection
```php
$query = '$skip=5&$top=10&$select=title,price';
$paged = $mw->apply($crud, $query);
$fields = $mw->getFields();
foreach ($paged->select(...$fields) as $row) {
    // process $row
}
```

### Parsing an HTTP query string
```php
$odata = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
$rows  = $mw->query($odata);
```
