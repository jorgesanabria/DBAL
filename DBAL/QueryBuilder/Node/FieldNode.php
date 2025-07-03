<?php
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;

/**
 * Clase/Interfaz FieldNode
 */
class FieldNode extends NotImplementedNode
{
/** @var mixed */
	protected $isEmpty = false;
/** @var mixed */
	protected $field;
/**
 * __construct
 * @param mixed $field
 * @return void
 */

	public function __construct($field)
	{
		$this->field = $field;
	}
/**
 * send
 * @param MessageInterface $message
 * @return mixed
 */

	public function send(MessageInterface $message)
	{
		return $message->insertAfter($this->field, MessageInterface::SEPARATOR_COMMA);
	}
}
