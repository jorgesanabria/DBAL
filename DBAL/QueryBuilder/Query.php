<?php
namespace DBAL\QueryBuilder;

use DBAL\Entity;
use DBAL\ResultIterator;
use DBAL\QueryBuilder\MessageInterface;
use DBAL\QueryBuilder\Message;
use DBAL\QueryBuilder\Node\QueryNode;
use DBAL\QueryBuilder\Node\TablesNode;
use DBAL\QueryBuilder\Node\TableNode;
use DBAL\QueryBuilder\Node\FieldsNode;
use DBAL\QueryBuilder\Node\FieldNode;
use DBAL\QueryBuilder\Node\JoinsNode;
use DBAL\QueryBuilder\Node\JoinNode;
use DBAL\QueryBuilder\Node\WhereNode;
use DBAL\QueryBuilder\Node\HavingNode;
use DBAL\QueryBuilder\Node\FilterNode;
use DBAL\QueryBuilder\Node\GroupNode;
use DBAL\QueryBuilder\Node\OrderNode;
use DBAL\QueryBuilder\Node\LimitNode;
use DBAL\QueryBuilder\Node\ChangeNode;

class Query extends QueryNode
{
	public function from(...$tables)
	{
		$clon = clone $this;
		foreach ($tables as $table) {
			if (!$table instanceof TableNode)
				$_table = new TableNode($table);
			$clon->getChild('tables')->appendChild($_table, $table);
		}
		return $clon;
	}
	protected function join($type, $table, array $on = [])
	{
		$this->getChild('joins')->appendChild(new JoinNode($table, $type, $on));
	}
	public function innerJoin($table, array ...$on)
	{
		$clon = clone $this;
		$clon->join(JoinNode::INNER_JOIN, $table, $on);
		return $clon;
	}
	public function leftJoin($table, array ...$on)
	{
		$clon = clone $this;
		$clon->join(JoinNode::LEFT_JOIN, $table, $on);
		return $clon;
	}
	public function rightJoin($table, array ...$on)
	{
		$clon = clone $this;
		$clon->join(JoinNode::RIGHT_JOIN, $table, $on);
		return $clon;
	}
	public function where(array ...$filters)
	{
		$clon = clone $this;
		foreach ($filters as $filter) 
			$clon->getChild('where')->appendChild(new FilterNode($filter));
		return $clon;
	}
	public function having(array ...$filters)
	{
		$clon = clone $this;
		foreach ($filters as $filter)
			$clon->getChild('having')->appendChild(new FilterNode($filter));
		return $clon;
	}
	public function group(...$fields)
	{
		$clon = clone $this;
		foreach ($fields as $field)
			$clon->getChild('group')->appendChild(new FieldNode($field));
		return $clon;
	}
	public function order($type, array $fields)
	{
		$clon = clone $this;
		foreach ($fields as $field)
			$clon->getChild('order')->appendChild(new FieldNode(sprintf('%s %s', $field, $type)));
		return $clon;
	}
	public function desc(...$fields)
	{
		$clon = clone $this;
		return $clon->order(OrderNode::ORDER_DESC, $fields);
	}
	public function asc(...$fields)
	{
		$clon = clone $this;
		return $clon->order(OrderNode::ORDER_ASC, $fields);
	}
	public function limit($limit)
	{
		$clon = clone $this;
		$clon->getChild('limit')->setLimit($limit);
		return $clon;
	}
	public function offset($offset)
	{
		$clon = clone $this;
		$clon->getChild('limit')->setOffset($offset);
		return $clon;
	}
	public function buildSelect(...$fields)
	{
		$clon = clone $this;
		$message = new Message(MessageInterface::MESSAGE_TYPE_SELECT);
		if (sizeof($fields) == 0) {
			$message = $clon->send($message);
		} else {
			$old = $clon->removeChild('fields');
			$clon->appendChild(new FieldsNode, 'fields');
			foreach ($fields as $field) {
				if (!$field instanceof FieldNode)
					$field = new FieldNode($field);
				$clon->getChild('fields')->appendChild($field);
			}
			$message = $clon->send($message);
			$clon->removeChild('fields');
			$clon->appendChild($old, 'fields');
		}
		return $message;
	}
	public function buildInsert(array $fields)
	{
		$clon = clone $this;
		$message = new Message(MessageInterface::MESSAGE_TYPE_INSERT);
		$clon->getChild('change')->setFields($fields);
		$message = $clon->send($message);
		return $message;
	}
	public function buildUpdate(array $fields)
	{
		$clon = clone $this;
		$message = new Message(MessageInterface::MESSAGE_TYPE_UPDATE);
		$clon->getChild('change')->setFields($fields);
		$message = $clon->send($message);
		return $message;
	}
	public function buildDelete()
	{
		$clon = clone $this;
		$message = new Message(MessageInterface::MESSAGE_TYPE_DELETE);
		$message = $clon->send($message);
		return $message;
	}
}