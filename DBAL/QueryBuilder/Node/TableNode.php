<?php
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;

/**
 * Node that represents a table or table expression used in FROM/INSERT/UPDATE
 * clauses.
 */
class TableNode extends NotImplementedNode
{
        /** @var bool */
        protected bool $isEmpty = false;
        /**
         * @param mixed $table Table name or expression.
         */
        public function __construct(private mixed $table)
        {
        }
        /**
         * Append the table expression to the message.
         */
        public function send(MessageInterface $message)
        {
                return $message->insertAfter($this->table, MessageInterface::SEPARATOR_COMMA);
        }
}
