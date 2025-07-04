# Supported Database Engines

DBAL uses PHP's PDO extension as its only dependency. This means it can work with any database that has a PDO driver available. The library ships with platform classes for some engines to handle SQL differences like `LIMIT` syntax and auto‑increment keywords.

## Built‑in Platforms

- **SQLite** (`SqlitePlatform`) – Default behaviour when no platform is passed. It understands SQLite's `LIMIT` syntax and uses `AUTOINCREMENT` for auto‑increment columns.
- **PostgreSQL** (`PostgresPlatform`) – Adapts `LIMIT`/`OFFSET` clauses and sets the `SERIAL` keyword for auto‑increment fields.
- **SQL Server** (`SqlServerPlatform`) – Uses the `OFFSET/FETCH` pattern and the `IDENTITY(1,1)` keyword.
- **MySQL/MariaDB** (`MysqlPlatform`, `MariaDbPlatform`) – Share the same dialect, using `LIMIT` and the `AUTO_INCREMENT` keyword.

Select the appropriate platform when creating a `Crud` instance:

```php
use DBAL\Platform\PostgresPlatform;

$pdo  = new PDO('pgsql:host=localhost;dbname=demo');
$crud = new DBAL\Crud($pdo, new PostgresPlatform());
```

## Adding Support for Other Engines

New engines can be supported by implementing `DBAL\Platform\PlatformInterface` and providing the SQL variations required by that engine. At minimum you must implement:

```php
interface PlatformInterface
{
    public function applyLimitOffset(MessageInterface $message, ?int $limit, ?int $offset): MessageInterface;
    public function autoIncrementKeyword(): string;
}
```

1. **Create your platform class** implementing these methods.
2. Use the class when constructing `Crud` or `Query` objects.

Example skeleton for MySQL:

```php
namespace DBAL\Platform;

use DBAL\QueryBuilder\MessageInterface;

class MysqlPlatform implements PlatformInterface
{
    public function applyLimitOffset(MessageInterface $msg, ?int $limit, ?int $offset): MessageInterface
    {
        if ($limit === null && $offset === null) {
            return $msg;
        }
        $sql = 'LIMIT ';
        $values = [];
        if ($limit !== null) {
            $sql .= '?';
            $values[] = $limit;
        }
        if ($offset !== null) {
            $sql .= $limit !== null ? ' OFFSET ?' : '?, ?';
            $values[] = $offset;
        }
        return $msg->addValues($values)->insertAfter($sql);
    }

    public function autoIncrementKeyword(): string
    {
        return 'AUTO_INCREMENT';
    }
}
```

Once implemented you can use it in your application:

```php
$crud = new DBAL\Crud($pdo, new MysqlPlatform());
```

This design keeps the core library lightweight while allowing developers to adapt it to any SQL dialect supported by PDO.
