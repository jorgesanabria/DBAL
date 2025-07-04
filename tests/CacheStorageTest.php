<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use DBAL\MemoryCacheStorage;
use DBAL\SqliteCacheStorage;

class CacheStorageTest extends TestCase
{
    public function testMemoryCacheStorage(): void
    {
        $storage = new MemoryCacheStorage();
        $this->assertNull($storage->get('foo'));
        $storage->set('foo', 'bar');
        $this->assertSame('bar', $storage->get('foo'));
        $storage->delete('foo');
        $this->assertNull($storage->get('foo'));
        $storage->set('a', 1);
        $storage->set('b', 2);
        $storage->delete();
        $this->assertNull($storage->get('a'));
        $this->assertNull($storage->get('b'));
    }

    public function testSqliteCacheStorage(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'cache');
        $storage = new SqliteCacheStorage($file);
        $this->assertNull($storage->get('foo'));
        $storage->set('foo', 'bar');
        $this->assertSame('bar', $storage->get('foo'));
        $storage->delete('foo');
        $this->assertNull($storage->get('foo'));
        $storage->set('a', 1);
        $storage->set('b', 2);
        $storage->delete();
        $this->assertNull($storage->get('a'));
        $this->assertNull($storage->get('b'));
        unlink($file);
    }
}
