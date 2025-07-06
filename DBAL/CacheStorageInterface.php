<?php
declare(strict_types=1);
namespace DBAL;

/**
 * Interface for simple key-value cache backends.
 */
interface CacheStorageInterface
{
/**
 * Retrieve a cached value by key.
 *
 * @param string $key
 * @return mixed
 */

    public function get(string $key);
/**
 * Store a value in the cache.
 *
 * @param string $key
 * @param mixed  $value
 */

    public function set(string $key, $value): void;
    /**
     * Delete a cached value. If $key is null, all entries MUST be removed.
     */
    public function delete(string $key = null): void;
}
