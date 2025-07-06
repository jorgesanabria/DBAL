<?php
declare(strict_types=1);
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;

/**
 * Base interface for all query middleware components.
 */
interface MiddlewareInterface
{
/**
 * Process the given SQL message before or after execution.
 *
 * @param MessageInterface $message
 */
    public function __invoke(MessageInterface $message): void;
}
