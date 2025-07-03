<?php
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;
use DBAL\QueryBuilder\Message;

class ChangeNode extends NotImplementedNode
{
	protected $isEmpty = false;
	protected $fields = [];
	public function setFields(array $fields)
	{
		$this->fields = $fields;
	}
	public function send(MessageInterface $message)
	{
		$msg = null;
		if ($message->type() == MessageInterface::MESSAGE_TYPE_INSERT) {
			$msg = new Message(MessageInterface::MESSAGE_TYPE_INSERT, 'VALUES', array_values($this->fields));
			$fields = sprintf('(%s)', implode(', ', array_keys($this->fields)));
			$msg = $msg->insertBefore($fields);
			$q = sprintf('(%s)', implode(', ', array_fill(0, sizeof($this->fields), '?')));
			$msg = $msg->insertAfter($q);
		} else if ($message->type() == MessageInterface::MESSAGE_TYPE_UPDATE) {
			$msg = new Message(MessageInterface::MESSAGE_TYPE_UPDATE, 'SET', array_values($this->fields));
			$fields = implode(', ',
				array_map(
					function($field)
					{
						return sprintf('%s = ?', $field);
					},
					array_keys($this->fields)
				)
			);
			$msg = $msg->insertAfter($fields);
		}
		$this->fields = [];
		return ($msg != null)? $message->insertAfter($msg->readMessage())->addValues($msg->getValues()) : $message;
	}
}
