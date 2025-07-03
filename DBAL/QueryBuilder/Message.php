<?php
namespace DBAL\QueryBuilder;

/**
 * Clase/Interfaz Message
 */
class Message implements MessageInterface
{
/** @var mixed */
	protected $type;
/** @var mixed */
	protected $message;
/** @var mixed */
	protected $values;
/**
 * __construct
 * @param mixed $type
 * @param mixed $message
 * @param mixed $values
 * @return void
 */

	public function __construct($type = MessageInterface::MESSAGE_TYPE_SELECT, $message = '', $values = [])
	{
		$this->type = $type;
		$this->message = $message;
		$this->values = $values;
	}
/**
 * join
 * @param MessageInterface $message
 * @param mixed $separator
 * @return mixed
 */

	public function join(MessageInterface $message, $separator = MessageInterface::SEPARATOR_SPACE)
	{
		if ($this->type != $message->type())
		{
			throw new \InvalidArgumentException('Type of message passed to method "join" is not equals to self this type');
		}
		$clon = $this->insertAfter($message->readMessage(), $separator);
		$clon = $clon->addValues($message->values);
		return $clon; 
	}
/**
 * insertBefore
 * @param mixed $string
 * @param mixed $separator
 * @return mixed
 */

	public function insertBefore($string, $separator = MessageInterface::SEPARATOR_SPACE)
	{
		$clon = clone $this;
		if (strlen($clon->message) > 0)
			$clon->message = $string . $separator . $clon->message;			
		else
			$clon->message = $string;
		return $clon;
	}
/**
 * replace
 * @param mixed $old
 * @param mixed $now
 * @return mixed
 */

	public function replace($old, $now)
	{
		$clon = clone $this;
		$clon->message = str_replace($old, $now, $clon->message);
		return $clon;
	}
/**
 * insertAfter
 * @param mixed $string
 * @param mixed $separator
 * @return mixed
 */

	public function insertAfter($string, $separator = MessageInterface::SEPARATOR_SPACE)
	{
		$clon = clone $this;
		if (strlen($clon->message) > 0)
			$clon->message = $clon->message . $separator . $string;		
		else
			$clon->message = $string;
		return $clon;
	}
/**
 * addValues
 * @param array $values
 * @return mixed
 */

	public function addValues(array $values)
	{
		$clon = clone $this;
		$clon->values = array_merge($clon->values, $values);
		return $clon;
	}
/**
 * getValues
 * @return mixed
 */

	public function getValues()
	{
		return $this->values;
	}
/**
 * numValues
 * @return mixed
 */

	public function numValues()
	{
		return count($this->values);
	}
/**
 * getLength
 * @return mixed
 */

	public function getLength()
	{
		return strlen($this->message);
	}
/**
 * readMessage
 * @return mixed
 */

	public function readMessage()
	{
		return $this->message;
	}
/**
 * type
 * @return mixed
 */

	public function type()
	{
		return $this->type;
	}
}
