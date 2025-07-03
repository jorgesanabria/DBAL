<?php
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;

/**
 * Clase/Interfaz EmptyNode
 */
class EmptyNode extends NotImplementedNode
{
/** @var mixed */
	protected $isEmpty = true;
/**
 * send
 * @param MessageInterface $message
 * @return mixed
 */

	public function send(MessageInterface $message)
	{
		return $message;
	}
/**
 * isEmpty
 * @return mixed
 */

	public function isEmpty()
	{
		return $this->isEmpty;
	}
}
