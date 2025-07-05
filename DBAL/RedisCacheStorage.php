<?php
declare(strict_types=1);
namespace DBAL;

/**
 * Redis backed cache storage.
 *
 * Keys are automatically prefixed to avoid collisions with other data.
 */
class RedisCacheStorage implements CacheStorageInterface
{
    /**
     * Generic Redis client instance.
     *
     * The object must implement the subset of the Redis API used by this
     * storage (get, set, del and keys).
     */
    private object $redis;
    private string $prefix;

    public function __construct(object $redis, string $prefix = 'dbal:')
    {
        $this->redis = $redis;
        $this->prefix = $prefix;
    }

    public function get(string $key)
    {
        $val = $this->redis->get($this->prefix . $key);
        return $val === false ? null : json_decode($val, true);
    }

    public function set(string $key, $value): void
    {
        $this->redis->set($this->prefix . $key, json_encode($value));
    }

    public function delete(string $key = null): void
    {
        if ($key === null) {
            foreach ($this->redis->keys($this->prefix . '*') as $k) {
                $this->redis->del($k);
            }
        } else {
            $this->redis->del($this->prefix . $key);
        }
    }
}
