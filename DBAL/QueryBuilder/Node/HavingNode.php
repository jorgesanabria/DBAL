<?php
declare(strict_types=1);
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;
use DBAL\QueryBuilder\Message;
use DBAL\QueryBuilder\Node\NodeInterface;

/**
 * Node representing a SQL `HAVING` clause.
 *
 * Only {@see FilterNode} children are accepted and will be combined to form
 * the final HAVING expression.
 */
class HavingNode extends Node
{
        /** @var bool */
        protected bool $isEmpty = false;
        /**
         * Build the HAVING clause and append it to the given message.
         *
         * @param MessageInterface $message Message being built.
         * @return MessageInterface         Message with the HAVING clause.
         */
        public function send(MessageInterface $message)
        {
                $msg = new Message($message->type());
                foreach ($this->allChildren() as $child) {
                        $msg = $child->send($msg);
                }
                return ($msg->getLength() > 0)? $message->join($msg->insertBefore('HAVING')) : $message;
        }
        /**
         * Append a filter condition to this node.
         *
         * @param NodeInterface $node Filter node.
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
