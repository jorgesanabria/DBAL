<?php
declare(strict_types=1);
namespace DBAL;

/**
 * Clase/Interfaz MemoryCacheStorage
 */
class MemoryCacheStorage implements CacheStorageInterface
{
    private array $data = [];

/**
 * get
 * @param string $key
 * @return mixed|null
 */

    public function get(string $key)
    {
        return $this->data[$key] ?? null;
    }

/**
 * set
 * @param string $key
 * @param mixed $value
 * @return void
 */

    public function set(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

/**
 * delete
 * @param string $key
 * @return void
 */

    public function delete(string $key = null): void
    {
        if ($key === null) {
            $this->data = [];
        } else {
            unset($this->data[$key]);
        }
    }
}
