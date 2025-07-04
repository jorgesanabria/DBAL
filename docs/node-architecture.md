# Query Node Architecture

DBAL's query builder is powered by `QueryNode`, the root of the tree that assembles SQL statements. Each child node is responsible for generating a specific fragment of the final query:

- **TablesNode** – builds the table list used for `FROM`, `INSERT INTO`, `UPDATE` or `DELETE FROM` clauses.
- **FieldsNode** – composes the field list for `SELECT` statements. When empty it defaults to `SELECT *`.
- **JoinsNode** – contains several `JoinNode` children representing `JOIN` clauses.
- **WhereNode** – holds `FilterNode` objects that make up the `WHERE` conditions.
- **HavingNode** – same as `WhereNode` but for the `HAVING` part of grouped queries.
- **GroupNode** – lists fields used in the `GROUP BY` clause.
- **OrderNode** – lists expressions used in the `ORDER BY` clause.
- **LimitNode** – adds `LIMIT` and `OFFSET` fragments using the configured SQL dialect.
- **ChangeNode** – stores field/value pairs or multiple rows for `INSERT` and `UPDATE` operations.

`QueryNode::build()` walks this tree depending on the message type so only the necessary nodes contribute to the resulting SQL.

## Replacing nodes

Nodes can be removed or swapped to change how a query is built. The tree can be manipulated directly:

```php
$query->removeChild('order');
$query->appendChild(new MyOrderNode(), 'order');
```

Custom nodes provide complete control over their SQL fragment. After altering the tree, call the corresponding `build*()` method or `select()`, `insert()` and so on.

### Using middlewares

Middlewares can modify the query tree before execution. A middleware implementing `CrudAwareMiddlewareInterface` can expose helper methods that alter the underlying query:

```php
use DBAL\CrudAwareMiddlewareInterface;
use DBAL\MiddlewareInterface;
use DBAL\QueryBuilder\Node\FieldNode;
use DBAL\QueryBuilder\Node\OrderNode;
use DBAL\QueryBuilder\Query;
use DBAL\Crud;
use DBAL\QueryBuilder\MessageInterface;

class DefaultSortMiddleware implements MiddlewareInterface, CrudAwareMiddlewareInterface
{
    public function __invoke(MessageInterface $msg): void
    {
        // no-op
    }

    // Called explicitly through Crud::__call()
    public function applyDefaultSort(Crud $crud): Crud
    {
        $crud->removeChild('order');
        $crud->appendChild(new OrderNode());
        $crud->getChild('order')->appendChild(new FieldNode('created_at DESC'));
        return $crud;
    }
}
```

When the middleware is registered with a `Crud` instance you can call `$crud->applyDefaultSort()` before running the query. This pattern lets middlewares prepare or modify the query tree prior to building the final SQL.
