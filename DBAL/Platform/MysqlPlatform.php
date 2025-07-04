<?php
declare(strict_types=1);
namespace DBAL\Platform;

use DBAL\QueryBuilder\MessageInterface;

/**
 * MySQL/MariaDB specific SQL dialect implementation.
 */
class MysqlPlatform implements PlatformInterface
{
    public function applyLimitOffset(MessageInterface $message, ?int $limit, ?int $offset): MessageInterface
    {
        if ($limit === null && $offset === null) {
            return $message;
        }

        $sql = 'LIMIT ';
        $values = [];
        if ($limit !== null) {
            $sql .= '?';
            $values[] = $limit;
        }
        if ($offset !== null) {
            $sql .= $limit !== null ? ' OFFSET ?' : '?, ?';
            $values[] = $offset;
        }
        return $message->addValues($values)->insertAfter($sql);
    }

    public function autoIncrementKeyword(): string
    {
        return 'AUTO_INCREMENT';
    }
}
