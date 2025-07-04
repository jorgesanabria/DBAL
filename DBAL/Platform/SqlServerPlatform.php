<?php
declare(strict_types=1);
namespace DBAL\Platform;

use DBAL\QueryBuilder\MessageInterface;

/**
 * SQL Server specific SQL dialect implementation.
 */
class SqlServerPlatform implements PlatformInterface
{
    public function applyLimitOffset(MessageInterface $message, ?int $limit, ?int $offset): MessageInterface
    {
        if ($limit === null && $offset === null) {
            return $message;
        }

        $off = $offset ?? 0;
        $values = [$off];
        $sql = 'OFFSET ? ROWS';
        if ($limit !== null) {
            $sql .= ' FETCH NEXT ? ROWS ONLY';
            $values[] = $limit;
        }
        return $message->addValues($values)->insertAfter($sql);
    }

    public function autoIncrementKeyword(): string
    {
        return 'IDENTITY(1,1)';
    }
}
