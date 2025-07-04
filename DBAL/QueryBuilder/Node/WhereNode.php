<?php
declare(strict_types=1);
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;
use DBAL\QueryBuilder\Message;
use DBAL\QueryBuilder\Node\NodeInterface;

/**
 * Node that builds a SQL `WHERE` clause.
 *
 * Children must be {@see FilterNode} instances which generate the individual
 * filter expressions joined together in the order they were added.
 */
class WhereNode extends Node
{
        /** @var bool */
        protected bool $isEmpty = false;
        /**
         * Build the WHERE clause and append it to the given message.
         *
         * @param MessageInterface $message Message being built.
         * @return MessageInterface         Message with the WHERE clause added.
         */
        public function send(MessageInterface $message)
        {
                $msg = new Message($message->type());
                foreach ($this->allChildren() as $child) {
                        $msg = $child->send($msg);
                }
                return ($msg->getLength() > 0)? $message->join($msg->insertBefore('WHERE')) : $message;
        }
        /**
         * Append a filter to this WHERE node.
         *
         * @param NodeInterface $node Filter node to append.
         * @param string|null   $name Optional node name.
         * @return string|null
         */
        public function appendChild(NodeInterface $node, $name = null)
        {
                if ($node instanceof FilterNode) {
                        $name = parent::appendChild($node, $name);
                }
                return $name;
	}
}
