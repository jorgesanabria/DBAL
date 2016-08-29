<?php
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;

class EmtyNode extends NotImplementedNode
{
	protected $isEmpty = true;
	public function send(MessageInterface $message)
	{
		return $message;
	}
	public function isEmpty()
	{
		return $this->isEmpty;
	}
}