# Extending DBAL with Custom Nodes

DBAL's query builder is made of small nodes that generate fragments of SQL. While the built-in nodes cover the most common clauses, you can create your own node classes when you need features not available in the core.
For a breakdown of the default nodes see [node-architecture.md](node-architecture.md).

## Example: ON DUPLICATE KEY UPDATE

The following snippet creates a simple node that appends an `ON DUPLICATE KEY UPDATE` clause when inserting rows on MySQL:

```php
use DBAL\QueryBuilder\Node\Node;
use DBAL\QueryBuilder\MessageInterface;

class UpsertNode extends Node
{
    protected bool $isEmpty = false;
    private string $expr = '';

    public function onDuplicate(string $expr): self
    {
        $this->expr = $expr;
        return $this;
    }

    public function send(MessageInterface $msg)
    {
        if ($this->expr !== '') {
            $msg = $msg->insertAfter('ON DUPLICATE KEY UPDATE ' . $this->expr);
            $this->expr = '';
        }
        return $msg;
    }
}
```

To use it, append the node to the query tree and call it after the regular insert nodes:

```php
use DBAL\QueryBuilder\Query;
use DBAL\QueryBuilder\Message;
use DBAL\QueryBuilder\MessageInterface;

$query  = (new Query())->from('users');
$upsert = (new UpsertNode())->onDuplicate('name = VALUES(name)');
$query->appendChild($upsert, 'upsert');

$msg = DBAL\QueryBuilder\Node\QueryNode::build(
    $query,
    new Message(MessageInterface::MESSAGE_TYPE_INSERT)
        ->addValues(['id' => 1, 'name' => 'Alice'])
);
```

`QueryNode::build()` processes the custom node together with the standard ones, producing:

```sql
INSERT INTO users (id, name) VALUES (?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)
```

## Example: RETURNING clause on PostgreSQL

Another common extension is requesting inserted values back. This node adds a `RETURNING` clause:

```php
class ReturningNode extends Node
{
    protected bool $isEmpty = false;
    private string $fields = '';

    public function fields(string $list): self
    {
        $this->fields = $list;
        return $this;
    }

    public function send(MessageInterface $msg)
    {
        if ($this->fields !== '') {
            $msg = $msg->insertAfter('RETURNING ' . $this->fields);
            $this->fields = '';
        }
        return $msg;
    }
}
```

After appending the node you can build an INSERT statement that returns the new `id`:

```php
$query     = (new Query())->from('users');
$returning = (new ReturningNode())->fields('id');
$query->appendChild($returning, 'returning');

$msg = DBAL\QueryBuilder\Node\QueryNode::build(
    $query,
    new Message(MessageInterface::MESSAGE_TYPE_INSERT)
        ->addValues(['name' => 'Bob'])
);
```

Resulting SQL:

```sql
INSERT INTO users (name) VALUES (?) RETURNING id
```

Custom nodes let you adapt DBAL to any SQL dialect without modifying the core library.
