<?php
declare(strict_types=1);
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;

/**
 * Node that converts INSERT INTO statements to REPLACE INTO.
 */
class ReplaceNode extends Node
{
    protected bool $isEmpty = false;

    public function send(MessageInterface $message)
    {
        return $message->replace('INSERT INTO', 'REPLACE INTO');
    }
}
