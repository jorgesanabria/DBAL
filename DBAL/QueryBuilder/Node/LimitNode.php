<?php
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;

class LimitNode extends NotImplementedNode
{
	protected $isEmpty = false;
	protected $limit = null;
	protected $offset = null;
	public function setLimit($limit)
	{
		$this->limit = $limit;
	}
	public function setOffset($offset)
	{
		$this->offset = $offset;
	}
	public function send(MessageInterface $message)
	{
		$msg = $message;
		if ($this->limit === null && $this->offset === null)
			$msg = $message;
		else if ($this->limit !== null && $this->offset === null)
			$msg = $message->addValues([$this->limit])->insertAfter('LIMIT ?');
		else if ($this->limit === null && $this->offset !== null)
			$msg = $message->addValues([$this->offset])->insertAfter('LIMIT -1 OFFSET ?');
		else if ($this->limit !== null && $this->offset !== null)
			$msg = $message->addValues([$this->limit, $this->offset])->insertAfter('LIMIT ? OFFSET ?');
		$this->limit = $this->offset = null;
		return $msg;
	}
}