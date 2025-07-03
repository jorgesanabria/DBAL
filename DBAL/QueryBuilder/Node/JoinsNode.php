<?php
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;
use DBAL\QueryBuilder\Message;
use DBAL\QueryBuilder\Node\NodeInterface;

/**
 * Clase/Interfaz JoinsNode
 */
class JoinsNode extends Node
{
/** @var mixed */
	protected $isEmpty = false;
/**
 * send
 * @param MessageInterface $message
 * @return mixed
 */

        public function send(MessageInterface $message)
        {
                $msg = new Message($message->type());
                foreach ($this->allChildren() as $child) {
                        $msg = $child->send($msg);
                }
                return ($msg->getLength() > 0) ? $message->join($msg) : $message;
        }
/**
 * appendChild
 * @param NodeInterface $node
 * @param mixed $name
 * @return mixed
 */

	public function appendChild(NodeInterface $node, $name = null)
	{
		if ($node instanceof JoinNode) {
			$name = parent::appendChild($node, $name);
		}
		return $name;
	}
}
