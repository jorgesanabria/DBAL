<?php
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;

class CacheMiddleware implements MiddlewareInterface
{
    private $storage;

    public function __construct(CacheStorageInterface $storage = null)
    {
        $this->storage = $storage ?: new MemoryCacheStorage();
    }

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

    private function key(MessageInterface $msg): string
    {
        return sha1($msg->readMessage() . '|' . serialize($msg->getValues()));
    }

    public function fetch(MessageInterface $msg)
    {
        if ($msg->type() !== MessageInterface::MESSAGE_TYPE_SELECT) {
            return null;
        }
        return $this->storage->get($this->key($msg));
    }

    public function save(MessageInterface $msg, array $rows): void
    {
        if ($msg->type() !== MessageInterface::MESSAGE_TYPE_SELECT) {
            return;
        }
        $this->storage->set($this->key($msg), $rows);
    }
}
