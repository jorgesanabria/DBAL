<?php
declare(strict_types=1);
namespace DBAL\Platform;

use DBAL\QueryBuilder\MessageInterface;

/**
 * PostgreSQL specific SQL dialect implementation.
 */
class PostgresPlatform implements PlatformInterface
{
    public function applyLimitOffset(MessageInterface $message, ?int $limit, ?int $offset): MessageInterface
    {
        if ($limit === null && $offset === null) {
            return $message;
        } elseif ($limit !== null && $offset === null) {
            return $message->addValues([$limit])->insertAfter('LIMIT ?');
        } elseif ($limit === null && $offset !== null) {
            return $message->addValues([$offset])->insertAfter('OFFSET ?');
        } else {
            return $message->addValues([$limit, $offset])->insertAfter('LIMIT ? OFFSET ?');
        }
    }

    public function autoIncrementKeyword(): string
    {
        return 'SERIAL';
    }
}
