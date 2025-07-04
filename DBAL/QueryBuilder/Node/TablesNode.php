<?php
declare(strict_types=1);
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;
use DBAL\QueryBuilder\Message;
use DBAL\QueryBuilder\Node\NodeInterface;

/**
 * Node that manages the list of tables used in a query.
 */
class TablesNode extends Node
{
        /** @var bool */
        protected bool $isEmpty = false;
        /**
         * Build the table list for the current query type.
         */
        public function send(MessageInterface $message)
        {
                $msg = new Message($message->type());
                foreach ($this->allChildren() as $child) {
                        $msg = $child->send($msg);
                }
		if ($message->type() == MessageInterface::MESSAGE_TYPE_SELECT) {
			$message = $message->join($msg->insertBefore('FROM'));
		} else if ($message->type() == MessageInterface::MESSAGE_TYPE_INSERT) {
			$message = $message->join($msg->insertBefore('INSERT INTO'));
		} else if ($message->type() == MessageInterface::MESSAGE_TYPE_UPDATE) {
			$message = $message->join($msg->insertBefore('UPDATE'));
		} else if ($message->type() == MessageInterface::MESSAGE_TYPE_DELETE) {
			$message = $message->join($msg->insertBefore('DELETE FROM'));
		}
		return $message;
	}
        /**
         * Append a table to this node.
         */
        public function appendChild(NodeInterface $node, $name = null)
        {
                if ($node instanceof TableNode) {
                        $name = parent::appendChild($node, $name);
                }
                return $name;
	}
}
