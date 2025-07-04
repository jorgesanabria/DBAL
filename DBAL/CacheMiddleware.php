<?php
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;

/**
 * Middleware that caches the result of SELECT statements.
 *
 * A custom {@see CacheStorageInterface} implementation can be passed to the
 * constructor. When omitted an in-memory storage is used. The entire cache is
 * flushed whenever an INSERT, UPDATE or DELETE query is executed.
 */
class CacheMiddleware implements MiddlewareInterface
{
    /**
     * Storage backend used to persist cached query results.
     */
    private CacheStorageInterface $storage;

    /**
     * Initialise the middleware.
     *
     * @param CacheStorageInterface|null $storage Optional custom cache storage
     *        implementation. If omitted an in-memory cache is used.
     */
    public function __construct(?CacheStorageInterface $storage = null)
    {
        $this->storage = $storage ?? new MemoryCacheStorage();
    }

/**
 * __invoke
 * @param MessageInterface $msg
 * @return void
 */

    public function __invoke(MessageInterface $msg): void
    {
        if (in_array($msg->type(), [
            MessageInterface::MESSAGE_TYPE_INSERT,
            MessageInterface::MESSAGE_TYPE_UPDATE,
            MessageInterface::MESSAGE_TYPE_DELETE,
        ])) {
            // Invalidate entire cache on data changes
            $this->storage->delete();
        }
    }

/**
 * key
 * @param MessageInterface $msg
 * @return string
 */

    private function key(MessageInterface $msg): string
    {
        return sha1($msg->readMessage() . '|' . serialize($msg->getValues()));
    }

    /**
     * Retrieve cached rows for the given SELECT message.
     *
     * Non-SELECT statements always return `null`.
     */
    public function fetch(MessageInterface $msg)
    {
        if ($msg->type() !== MessageInterface::MESSAGE_TYPE_SELECT) {
            return null;
        }
        return $this->storage->get($this->key($msg));
    }

    /**
     * Store rows for the provided SELECT message in the cache.
     */
    public function save(MessageInterface $msg, array $rows): void
    {
        if ($msg->type() !== MessageInterface::MESSAGE_TYPE_SELECT) {
            return;
        }
        $this->storage->set($this->key($msg), $rows);
    }
}
