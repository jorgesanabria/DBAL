<?php
declare(strict_types=1);
namespace DBAL\Platform;

use DBAL\QueryBuilder\MessageInterface;

/**
 * Interface for database platform specific SQL variations.
 */
interface PlatformInterface
{
    /**
     * Apply LIMIT and OFFSET clauses to the SQL message.
     */
    public function applyLimitOffset(MessageInterface $message, ?int $limit, ?int $offset): MessageInterface;

    /**
     * Keyword used to mark auto-increment columns in CREATE TABLE statements.
     */
    public function autoIncrementKeyword(): string;
}
