<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use DBAL\RedisCacheStorage;
use DBAL\CacheStorageInterface;

class FakeRedis
{
    public array $data = [];
    public array $calls = [];
    public function get($key)
    {
        $this->calls[] = ['get', $key];
        return $this->data[$key] ?? false;
    }
    public function set($key, $value)
    {
        $this->calls[] = ['set', $key, $value];
        $this->data[$key] = $value;
    }
    public function del($key)
    {
        $this->calls[] = ['del', $key];
        unset($this->data[$key]);
    }
    public function keys($pattern)
    {
        $this->calls[] = ['keys', $pattern];
        $prefix = rtrim($pattern, '*');
        return array_values(array_filter(array_keys($this->data), fn($k) => str_starts_with($k, $prefix)));
    }
}

class RedisCacheStorageTest extends TestCase
{
    public function testBasicOperations()
    {
        $redis = new FakeRedis();
        $storage = new RedisCacheStorage($redis, 'p:');

        $storage->set('k', ['v']);
        $this->assertEquals(['v'], $storage->get('k'));

        $storage->delete('k');
        $this->assertNull($storage->get('k'));

        $storage->set('a', 1);
        $storage->set('b', 2);
        $storage->delete();
        $this->assertNull($storage->get('a'));
        $this->assertNull($storage->get('b'));
    }
}
