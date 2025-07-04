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
    private \Redis $redis;
    private string $prefix;

    public function __construct(\Redis $redis, string $prefix = 'dbal:')
    {
        $this->redis = $redis;
        $this->prefix = $prefix;
    }

    public function get(string $key)
    {
        $val = $this->redis->get($this->prefix . $key);
        return $val === false ? null : unserialize($val);
    }

    public function set(string $key, $value): void
    {
        $this->redis->set($this->prefix . $key, serialize($value));
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
