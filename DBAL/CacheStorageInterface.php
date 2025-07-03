<?php
namespace DBAL;

/**
 * Clase/Interfaz CacheStorageInterface
 */
interface CacheStorageInterface
{
/**
 * get
 * @param string $key
 * @return mixed
 */

    public function get(string $key);
/**
 * set
 * @param string $key
 * @param mixed $value
 * @return void
 */

    public function set(string $key, $value): void;
    /**
     * Delete a cached value. If $key is null, all entries MUST be removed.
     */
    public function delete(string $key = null): void;
}
