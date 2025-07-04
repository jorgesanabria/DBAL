<?php
declare(strict_types=1);
namespace DBAL;

/**
 * Memcached backed cache storage.
 */
class MemcachedCacheStorage implements CacheStorageInterface
{
    /**
     * Generic memcached client instance.
     *
     * The object is expected to expose the standard Memcached API
     * used by this storage (get, set, delete and flush).
     */
    private object $memcached;
    private string $prefix;

    public function __construct(object $memcached, string $prefix = 'dbal:')
    {
        $this->memcached = $memcached;
        $this->prefix    = $prefix;
    }

    public function get(string $key)
    {
        $val = $this->memcached->get($this->prefix . $key);
        return $val === false ? null : $val;
    }

    public function set(string $key, $value): void
    {
        $this->memcached->set($this->prefix . $key, $value);
    }

    public function delete(string $key = null): void
    {
        if ($key === null) {
            $this->memcached->flush();
        } else {
            $this->memcached->delete($this->prefix . $key);
        }
    }
}
