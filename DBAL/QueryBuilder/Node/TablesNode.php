<?php
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;
use DBAL\QueryBuilder\Message;
use DBAL\QueryBuilder\Node\NodeInterface;

class TablesNode extends Node
{
	protected $isEmpty = false;
	public function send(MessageInterface $message)
	{
		$msg = new Message($message->type());
		foreach ($this->allChildren() as $child) {
			$_msg = $child->send($msg);
			$msg = $msg->join($_msg, MessageInterface::SEPARATOR_COMMA);
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
	public function appendChild(NodeInterface $node, $name = null)
	{
		if ($node instanceof TableNode) {
			$name = parent::appendChild($node, $name);
		}
		return $name;
	}
}