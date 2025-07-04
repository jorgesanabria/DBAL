<?php
declare(strict_types=1);
namespace DBAL\Schema\Node;

use DBAL\QueryBuilder\Node\Node;
use DBAL\QueryBuilder\MessageInterface;

/**
 * Node representing a single table definition fragment.
 */
class TableDefinitionNode extends Node
{
    protected bool $isEmpty = false;

    public function __construct(private string $definition)
    {
    }

    public function send(MessageInterface $message)
    {
        return $message->insertAfter($this->definition, MessageInterface::SEPARATOR_COMMA);
    }
}
