<?php
declare(strict_types=1);
namespace DBAL;

use PDO;

/**
 * Clase/Interfaz SqliteCacheStorage
 */
class SqliteCacheStorage implements CacheStorageInterface
{
    private PDO $pdo;
    private \DBAL\QueryBuilder\Query $query;

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
        $this->query = (new \DBAL\QueryBuilder\Query())->from('cache');
    }

/**
 * get
 * @param string $key
 * @return mixed|null
 */

    public function get(string $key)
    {
        $msg  = $this->query->where(['key' => $key])->buildSelect('value');
        $stmt = $this->pdo->prepare($msg->readMessage());
        $stmt->execute($msg->getValues());
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? json_decode($row['value'], true) : null;
    }

/**
 * set
 * @param string $key
 * @param mixed $value
 * @return void
 */

    public function set(string $key, $value): void
    {
        $msg = $this->query->buildInsert(['key' => $key, 'value' => json_encode($value)]);
        $msg = (new \DBAL\QueryBuilder\Node\ReplaceNode())->send($msg);
        $stmt = $this->pdo->prepare($msg->readMessage());
        $stmt->execute($msg->getValues());
    }

/**
 * delete
 * @param string $key
 * @return void
 */

    public function delete(string $key = null): void
    {
        if ($key === null) {
            $msg = $this->query->buildDelete();
            $stmt = $this->pdo->prepare($msg->readMessage());
            $stmt->execute($msg->getValues());
        } else {
            $msg = $this->query->where(['key' => $key])->buildDelete();
            $stmt = $this->pdo->prepare($msg->readMessage());
            $stmt->execute($msg->getValues());
        }
    }
}
