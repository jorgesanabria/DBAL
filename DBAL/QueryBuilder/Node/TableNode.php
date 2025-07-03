<?php
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;

/**
 * Clase/Interfaz TableNode
 */
class TableNode extends NotImplementedNode
{
/** @var mixed */
	protected $isEmpty = false;
/** @var mixed */
	protected $table;
/**
 * __construct
 * @param mixed $table
 * @return void
 */

	public function __construct($table)
	{
		$this->table = $table;
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
