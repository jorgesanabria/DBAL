<?php
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;

class JoinNode extends NotImplementedNode
{
	const INNER_JOIN = 'INNER JOIN';
	const LEFT_JOIN = 'LEFT JOIN';
	const RIGHT_JOIN = 'RIGHT JOIN';
	protected $isEmpty = false;
	protected $table;
	protected $type;
	protected $on = [];
	public function __construct($table, $type = JoinNode::INNER_JOIN, array $on = [])
	{
		$this->table = $table;
		$this->table = $type;
		foreach ($on as $filter)
			$this->on[] = new FilterNode($on);
	}
	public function send(MessageInterface $message)
	{
		$msg = new Message($message->type(), sprintf('%s %s', $this->type, $this->table));
		if (sizeof($this->on) > 0) {
			foreach ($this->on as $filter)
				$msg = $filter->send($msg);
		}
		return $message->join($msg);
	}
	public function __clone()
	{
		foreach ($this->on as $key=>$node)
			$this->on[$key] = clone $node;
	}
}