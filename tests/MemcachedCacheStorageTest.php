<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use DBAL\MemcachedCacheStorage;

class FakeMemcached
{
    public array $data = [];
    public function get($key)
    {
        return $this->data[$key] ?? false;
    }
    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }
    public function delete($key)
    {
        unset($this->data[$key]);
    }
    public function flush()
    {
        $this->data = [];
    }
}

class MemcachedCacheStorageTest extends TestCase
{
    public function testBasicOperations()
    {
        $mc = new FakeMemcached();
        $storage = new MemcachedCacheStorage($mc, 'p:');

        $storage->set('k', 1);
        $this->assertEquals(1, $storage->get('k'));

        $storage->delete('k');
        $this->assertNull($storage->get('k'));

        $storage->set('a', 'x');
        $storage->set('b', 'y');
        $storage->delete();
        $this->assertNull($storage->get('a'));
        $this->assertNull($storage->get('b'));
    }
}
