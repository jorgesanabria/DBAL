<?php
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;
use DBAL\QueryBuilder\Message;
use DBAL\QueryBuilder\Node\NodeInterface;

class WhereNode extends Node
{
	protected $isEmpty = false;
	public function send(MessageInterface $message)
	{
		$msg = new Message($message->type());
		foreach ($this->allChildren() as $child) {
			$msg = $child->send($msg);
		}
		return ($msg->getLength() > 0)? $message->join($msg->insertBefore('WHERE')) : $message;
	}
	public function appendChild(NodeInterface $node, $name = null)
	{
		if ($node instanceof FilterNode) {
			$name = parent::appendChild($node, $name);
		}
		return $name;
	}
}