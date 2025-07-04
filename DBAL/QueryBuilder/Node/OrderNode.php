<?php
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;
use DBAL\QueryBuilder\Message;
use DBAL\QueryBuilder\Node\NodeInterface;

/**
 * Node that produces an `ORDER BY` clause.
 *
 * Child nodes should be {@see FieldNode} instances containing the column and
 * direction (ASC or DESC).
 */
class OrderNode extends Node
{
        const ORDER_DESC = 'DESC';
        const ORDER_ASC = 'ASC';
        /** @var bool */
        protected bool $isEmpty = false;
        /**
         * Append the ORDER BY clause to the provided message.
         */
        public function send(MessageInterface $message)
        {
                $msg = new Message($message->type());
                foreach ($this->allChildren() as $child) {
                        $msg = $child->send($msg);
                }
                return ($msg->getLength() > 0)? $message->join($msg->insertBefore('ORDER BY')) : $message;
        }
        /**
         * Append a field ordering to this node.
         */
        public function appendChild(NodeInterface $node, $name = null)
        {
                if ($node instanceof FieldNode) {
                        $name = parent::appendChild($node, $name);
                }
                return $name;
	}
}
