<?php
declare(strict_types=1);
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;

/**
 * QueryBuilder node that returns the message unchanged.
 */
class EmptyNode extends NotImplementedNode
{
        protected bool $isEmpty = true;
/**
 * send
 * @param MessageInterface $message
 * @return MessageInterface
 */

	public function send(MessageInterface $message)
	{
		return $message;
	}
/**
 * isEmpty
 * @return bool
 */

	public function isEmpty()
	{
		return $this->isEmpty;
	}
}
