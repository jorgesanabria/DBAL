<?php
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;

class FieldNode extends NotImplementedNode
{
	protected $isEmpty = false;
	protected $field;
	public function __construct($field)
	{
		$this->field = $field;
	}
	public function send(MessageInterface $message)
	{
		return $message->insertAfter($this->field, MessageInterface::SEPARATOR_COMMA);
	}
}
