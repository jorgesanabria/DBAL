<?php
declare(strict_types=1);
namespace DBAL\Schema\Node;

use DBAL\QueryBuilder\Node\Node;
use DBAL\QueryBuilder\MessageInterface;
use DBAL\QueryBuilder\Message;

/**
 * Node used to build CREATE TABLE statements.
 */
class CreateTableNode extends Node
{
    protected bool $isEmpty = false;

    public function __construct(private string $table, private bool $ifNotExists = false)
    {
    }

    public function send(MessageInterface $message)
    {
        $defs = new Message($message->type());
        foreach ($this->allChildren() as $child) {
            $defs = $child->send($defs);
        }
        $template = $this->ifNotExists ? 'CREATE TABLE IF NOT EXISTS %s (%s)' : 'CREATE TABLE %s (%s)';
        $sql = sprintf($template, $this->table, $defs->readMessage());
        return $message->insertAfter($sql);
    }
}
