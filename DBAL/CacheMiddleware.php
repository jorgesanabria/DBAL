<?php
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;

/**
 * Clase/Interfaz CacheMiddleware
 */
class CacheMiddleware implements MiddlewareInterface
{
/** @var mixed */
    private $storage;

/**
 * __construct
 * @param CacheStorageInterface $storage
 * @return void
 */

    public function __construct(CacheStorageInterface $storage = null)
    {
        $this->storage = $storage ?: new MemoryCacheStorage();
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
 * fetch
 * @param MessageInterface $msg
 * @return mixed
 */

    public function fetch(MessageInterface $msg)
    {
        if ($msg->type() !== MessageInterface::MESSAGE_TYPE_SELECT) {
            return null;
        }
        return $this->storage->get($this->key($msg));
    }

/**
 * save
 * @param MessageInterface $msg
 * @param array $rows
 * @return void
 */

    public function save(MessageInterface $msg, array $rows): void
    {
        if ($msg->type() !== MessageInterface::MESSAGE_TYPE_SELECT) {
            return;
        }
        $this->storage->set($this->key($msg), $rows);
    }
}
