<?php
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;
use DBAL\QueryBuilder\Message;
use DBAL\QueryBuilder\NodeInterface;

/**
 * Clase/Interfaz QueryNode
 */
class QueryNode extends Node
{
        protected bool $isEmpty = false;
/**
 * __construct
 * @return void
 */

	public function __construct()
	{
		$this->appendChild(new TablesNode, 'tables');
		$this->appendChild(new FieldsNode, 'fields');
		$this->appendChild(new JoinsNode, 'joins');
		$this->appendChild(new WhereNode, 'where');
		$this->appendChild(new HavingNode, 'having');
		$this->appendChild(new GroupNode, 'group');
		$this->appendChild(new OrderNode, 'order');
		$this->appendChild(new LimitNode, 'limit');
		$this->appendChild(new ChangeNode, 'change');
	}
/**
 * send
 * @param MessageInterface $message
 * @return mixed
 */

	public function send(MessageInterface $message)
	{
		return self::build($this, $message);
	}
	public static function build(QueryNode $query, MessageInterface $message)
	{
		$use = [];
		if ($message->type() == MessageInterface::MESSAGE_TYPE_SELECT)
			$use = ['fields', 'tables', 'joins', 'where', 'group', 'having', 'order', 'limit'];
		else if ($message->type() == MessageInterface::MESSAGE_TYPE_INSERT)
			$use = ['tables', 'change'];
		else if ($message->type() == MessageInterface::MESSAGE_TYPE_UPDATE)
			$use = ['tables', 'change', 'where', 'order', 'limit'];
		else if ($message->type() == MessageInterface::MESSAGE_TYPE_DELETE)
			$use = ['tables', 'where', 'order', 'limit'];
		foreach ($use as $node)
			$message = $query->getChild($node)->send($message);
		return $message;
	}
}

