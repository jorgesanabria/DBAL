<?php
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;

/**
 * Clase/Interfaz LimitNode
 */
class LimitNode extends NotImplementedNode
{
/** @var mixed */
	protected $isEmpty = false;
/** @var mixed */
	protected $limit = null;
/** @var mixed */
	protected $offset = null;
/**
 * setLimit
 * @param mixed $limit
 * @return mixed
 */

	public function setLimit($limit)
	{
		$this->limit = $limit;
	}
/**
 * setOffset
 * @param mixed $offset
 * @return mixed
 */

	public function setOffset($offset)
	{
		$this->offset = $offset;
	}
/**
 * send
 * @param MessageInterface $message
 * @return mixed
 */

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
