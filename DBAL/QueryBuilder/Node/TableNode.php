<?php
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;

class TableNode extends NotImplementedNode
{
	protected $isEmpty = false;
	protected $table;
	public function __construct($table)
	{
		$this->table = $table;
	}
	public function send(MessageInterface $message)
	{
		return $message->insertAfter($this->table, MessageInterface::SEPARATOR_COMMA);
	}
}
