<?php
declare(strict_types=1);
namespace DBAL\Schema\Node;

use DBAL\QueryBuilder\Node\Node;
use DBAL\QueryBuilder\MessageInterface;
use DBAL\QueryBuilder\Message;

/**
 * Node used to build ALTER TABLE statements.
 */
class AlterTableNode extends Node
{
    protected bool $isEmpty = false;

    public function __construct(private string $table)
    {
    }

    public function send(MessageInterface $message)
    {
        $defs = new Message($message->type());
        foreach ($this->allChildren() as $child) {
            $defs = $child->send($defs);
        }
        $sql = sprintf('ALTER TABLE %s %s', $this->table, $defs->readMessage());
        return $message->insertAfter($sql);
    }
}
