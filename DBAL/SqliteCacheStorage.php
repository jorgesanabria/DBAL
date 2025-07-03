<?php
namespace DBAL;

use PDO;

class SqliteCacheStorage implements CacheStorageInterface
{
    private $pdo;

    public function __construct(string $file)
    {
        $this->pdo = new PDO('sqlite:' . $file);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS cache (key TEXT PRIMARY KEY, value BLOB)');
    }

    public function get(string $key)
    {
        $stmt = $this->pdo->prepare('SELECT value FROM cache WHERE key = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? unserialize($row['value']) : null;
    }

    public function set(string $key, $value): void
    {
        $stmt = $this->pdo->prepare('REPLACE INTO cache(key, value) VALUES(?, ?)');
        $stmt->execute([$key, serialize($value)]);
    }

    public function delete(string $key = null): void
    {
        if ($key === null) {
            $this->pdo->exec('DELETE FROM cache');
        } else {
            $stmt = $this->pdo->prepare('DELETE FROM cache WHERE key = ?');
            $stmt->execute([$key]);
        }
    }
}
