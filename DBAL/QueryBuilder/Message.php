<?php
namespace DBAL\QueryBuilder;

class Message implements MessageInterface
{
	protected $type;
	protected $message;
	protected $values;
	public function __construct($type = MessageInterface::MESSAGE_TYPE_SELECT, $message = '', $values = [])
	{
		$this->type = $type;
		$this->message = $message;
		$this->values = $values;
	}
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
	public function insertBefore($string, $separator = MessageInterface::SEPARATOR_SPACE)
	{
		$clon = clone $this;
		if (strlen($clon->message) > 0)
			$clon->message = $string . $separator . $clon->message;			
		else
			$clon->message = $string;
		return $clon;
	}
	public function replace($old, $now)
	{
		$clon = clone $this;
		$clon->message = str_replace($old, $now, $clon->message);
		return $clon;
	}
	public function insertAfter($string, $separator = MessageInterface::SEPARATOR_SPACE)
	{
		$clon = clone $this;
		if (strlen($clon->message) > 0)
			$clon->message = $clon->message . $separator . $string;		
		else
			$clon->message = $string;
		return $clon;
	}
	public function addValues(array $values)
	{
		$clon = clone $this;
		$clon->values = array_merge($clon->values, $values);
		return $clon;
	}
	public function getValues()
	{
		return $this->values;
	}
	public function numValues()
	{
		return count($this->values);
	}
	public function getLength()
	{
		return strlen($this->message);
	}
	public function readMessage()
	{
		return $this->message;
	}
	public function type()
	{
		return $this->type;
	}
}
