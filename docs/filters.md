# Custom and Dynamic Filters

DBAL provides a flexible filter system for building `WHERE` clauses. In addition to the standard operators like `eq`, `ne` or `gt`, you can register your own filters to encapsulate complex logic.

## Registering a filter

Use `FilterNode::filter()` to declare a new operator. The callback receives the field name, the value supplied and a message object where the SQL fragment must be appended.

```php
use DBAL\QueryBuilder\Node\FilterNode;

FilterNode::filter('startWith', function ($field, $value, $msg) {
    return $msg->insertAfter(sprintf('%s LIKE ?', $field))
               ->addValues([$value . '%']);
});
```

Once defined, the filter is available through dynamic methods or array conditions:

```php
$rows = $crud->where(['name__startWith' => 'Al'])->select('id', 'name');
```

```sql
SELECT id, name FROM users WHERE name LIKE 'Al%';
```

## Hiding complexity

Filters can encapsulate multiple expressions or even subqueries. This keeps your application code readable while complex conditions remain hidden inside the filter implementation.

```php
FilterNode::filter('available', function ($field, $value, $msg) {
    return $msg->insertAfter('(stock > 0 AND discontinued = 0)');
});

$products = (new DBAL\Crud($pdo))->from('products');
$rows = $products->where(['stock__available' => null])->select('id', 'name');
```

```sql
SELECT id, name FROM products WHERE (stock > 0 AND discontinued = 0);
```

By giving the complex expression a descriptive name, subsequent queries remain concise and easier to read.

## Subqueries and CASE expressions

Queries can now embed other queries or build `CASE` statements fluently. Use `Query::subQuery()` together with the `IN` filter to reference a subselect:

```php
$active = (new DBAL\QueryBuilder\Query())
    ->from('users')
    ->where(['status' => 'active'])
    ->subQuery('id');

$msg = (new DBAL\QueryBuilder\Query())
    ->from('posts')
    ->where(['user_id__in' => $active])
    ->buildSelect();
// SELECT * FROM posts WHERE user_id in (SELECT id FROM users WHERE status = ?)
```

The new `CaseNode` helper simplifies generating conditional expressions:

```php
use DBAL\QueryBuilder\Node\CaseNode;

$case = (new CaseNode())
    ->when('status = 1', "'active'")
    ->when('status = 0', "'inactive'")
    ->else("'unknown'")
    ->as('state');

(new DBAL\QueryBuilder\Query())
    ->from('users')
    ->buildSelect($case);
// SELECT CASE WHEN status = 1 THEN 'active' WHEN status = 0 THEN 'inactive' ELSE 'unknown' END AS state FROM users
```


## Custom filter builders

Dynamic methods can be tailored to your domain by extending `DynamicFilterBuilder`:

```php
use DBAL\QueryBuilder\CustomFilterBuilder;

$rows = $crud->where(function (CustomFilterBuilder $q) {
    $q->isWoman()->andNext()->status__eq('active');
})->select('id', 'name');
```

The `CustomFilterBuilder` class can define helpers that map to one or more
filters. For example:

```php
class CustomFilterBuilder extends DynamicFilterBuilder
{
    public function isWoman(): self
    {
        parent::__call('gender__eq', ['fem']);
        return $this;
    }
}
```

```sql
SELECT id, name FROM users WHERE gender = 'fem' AND status = 'active';
```
