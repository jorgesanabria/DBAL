<?php
declare(strict_types=1);
namespace DBAL\QueryBuilder;

/**
 * Mutable SQL statement with associated bound values.
 */
class Message implements MessageInterface
{
        protected int $type;
        protected string $message;
        protected array $values;
/**
 * __construct
 * @param int $type
 * @param string $message
 * @param array $values
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
 * @param string $separator
 * @return self
 */

	public function join(MessageInterface $message, $separator = MessageInterface::SEPARATOR_SPACE)
	{
		if ($this->type != $message->type())
		{
			throw new \InvalidArgumentException('Type of message passed to method "join" is not equals to self this type');
		}
		$clone = $this->insertAfter($message->readMessage(), $separator);
		$clone = $clone->addValues($message->values);
		return $clone; 
	}
/**
 * insertBefore
 * @param string $string
 * @param string $separator
 * @return self
 */

	public function insertBefore($string, $separator = MessageInterface::SEPARATOR_SPACE)
	{
		$clone = clone $this;
		if (strlen($clone->message) > 0)
			$clone->message = $string . $separator . $clone->message;			
		else
			$clone->message = $string;
		return $clone;
	}
/**
 * replace
 * @param string $old
 * @param string $now
 * @return self
 */

	public function replace($old, $now)
	{
		$clone = clone $this;
		$clone->message = str_replace($old, $now, $clone->message);
		return $clone;
	}
/**
 * insertAfter
 * @param string $string
 * @param string $separator
 * @return self
 */

	public function insertAfter($string, $separator = MessageInterface::SEPARATOR_SPACE)
	{
		$clone = clone $this;
		if (strlen($clone->message) > 0)
			$clone->message = $clone->message . $separator . $string;		
		else
			$clone->message = $string;
		return $clone;
	}
/**
 * addValues
 * @param array $values
 * @return self
 */

	public function addValues(array $values)
	{
		$clone = clone $this;
		$clone->values = array_merge($clone->values, $values);
		return $clone;
	}
/**
 * getValues
 * @return array
 */

	public function getValues()
	{
		return $this->values;
	}
/**
 * numValues
 * @return int
 */

	public function numValues()
	{
		return count($this->values);
	}
/**
 * getLength
 * @return int
 */

	public function getLength()
	{
		return strlen($this->message);
	}
/**
 * readMessage
 * @return string
 */

	public function readMessage()
	{
		return $this->message;
	}
/**
 * type
 * @return int
 */

	public function type()
	{
		return $this->type;
	}
}
