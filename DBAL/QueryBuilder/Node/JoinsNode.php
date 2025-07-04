<?php
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;
use DBAL\QueryBuilder\Message;
use DBAL\QueryBuilder\Node\NodeInterface;

/**
 * Container node for multiple JOIN clauses.
 */
class JoinsNode extends Node
{
        /** @var bool */
        protected bool $isEmpty = false;
        /**
         * Build all JOIN clauses and append them to the message.
         */
        public function send(MessageInterface $message)
        {
                $msg = new Message($message->type());
                foreach ($this->allChildren() as $child) {
                        $msg = $child->send($msg);
                }
                return ($msg->getLength() > 0) ? $message->join($msg) : $message;
        }
        /**
         * Append a join clause to this node.
         */
        public function appendChild(NodeInterface $node, $name = null)
        {
                if ($node instanceof JoinNode) {
                        $name = parent::appendChild($node, $name);
                }
                return $name;
	}
}
