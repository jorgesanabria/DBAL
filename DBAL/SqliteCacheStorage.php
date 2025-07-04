<?php
namespace DBAL;

use PDO;

/**
 * Clase/Interfaz SqliteCacheStorage
 */
class SqliteCacheStorage implements CacheStorageInterface
{
    private PDO $pdo;

/**
 * __construct
 * @param string $file
 * @return void
 */

    public function __construct(string $file)
    {
        $this->pdo = new PDO('sqlite:' . $file);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS cache (key TEXT PRIMARY KEY, value BLOB)');
    }

/**
 * get
 * @param string $key
 * @return mixed
 */

    public function get(string $key)
    {
        $stmt = $this->pdo->prepare('SELECT value FROM cache WHERE key = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? unserialize($row['value']) : null;
    }

/**
 * set
 * @param string $key
 * @param mixed $value
 * @return void
 */

    public function set(string $key, $value): void
    {
        $stmt = $this->pdo->prepare('REPLACE INTO cache(key, value) VALUES(?, ?)');
        $stmt->execute([$key, serialize($value)]);
    }

/**
 * delete
 * @param string $key
 * @return void
 */

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
