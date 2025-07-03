<?php
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;

/**
 * Clase/Interfaz TableNode
 */
class TableNode extends NotImplementedNode
{
        protected bool $isEmpty = false;
/**
 * __construct
 * @param mixed $table
 * @return void
 */

        public function __construct(private mixed $table)
        {
        }
/**
 * send
 * @param MessageInterface $message
 * @return mixed
 */

	public function send(MessageInterface $message)
	{
		return $message->insertAfter($this->table, MessageInterface::SEPARATOR_COMMA);
	}
}
