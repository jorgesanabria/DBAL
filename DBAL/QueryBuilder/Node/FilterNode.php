<?php
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;
use DBAL\QueryBuilder\Message;

class FilterNode extends NotImplementedNode
{
	protected static $filters = [];
	protected $isEmpty = false;
	protected $parts;
	public function __construct(array $parts)
	{
		$this->parts = $parts;
	}
	public function send(MessageInterface $message)
	{
		$msg = new Message($message->type());
		foreach ($this->parts as $condition=>$values) {
			$msg = self::filtering($condition, $values, $msg);
		}
		if (sizeof($this->parts) > 1)
			$msg = $msg->insertBefore('(')->insertAfter(')');
		return ($msg->getLength() > 0)? $message->join($msg, MessageInterface::SEPARATOR_AND) : $message;
	}
	public static function filter($name, callable $callback)
	{
		self::$filters[$name] = $callback;
	}
	protected static function filtering($condition, $values, MessageInterface $message)
	{
		$parts = explode('__', $condition);
		if (sizeof($parts) == 1 && isset(self::$filters['eq']))
		{
			return (self::$filters['eq'])($parts[0], $values, $message);
		}
		if (isset(self::$filters[$parts[1]]))
		{
			return (self::$filters[$parts[1]])($parts[0], $values, $message);
		}
		throw new \RuntimeException(sprintf('The filter "%s" is not exists', $parts[1]), 500);
	}
}


FilterNode::filter('eq', function($field, $value, $message)
{
	return $message->insertAfter(sprintf('%s = ?', $field), MessageInterface::SEPARATOR_OR)->addValues((array) $value);
});

FilterNode::filter('ne', function($field, $value, $message)
{
	return $message->insertAfter(sprintf('%s != ?', $field), MessageInterface::SEPARATOR_OR)->addValues((array) $value);
});

FilterNode::filter('gt', function($field, $value, $message)
{
	return $message->insertAfter(sprintf('%s > ?', $field), MessageInterface::SEPARATOR_OR)->addValues((array) $value);
});

FilterNode::filter('lt', function($field, $value, $message)
{
	return $message->insertAfter(sprintf('%s < ?', $field), MessageInterface::SEPARATOR_OR)->addValues((array) $value);
});

FilterNode::filter('ge', function($field, $value, $message)
{
	return $message->insertAfter(sprintf('%s >= ?', $field), MessageInterface::SEPARATOR_OR)->addValues((array) $value);
});

FilterNode::filter('le', function($field, $value, $message)
{
	return $message->insertAfter(sprintf('%s <= ?', $field), MessageInterface::SEPARATOR_OR)->addValues((array) $value);
});

FilterNode::filter('in', function($field, $values, $message)
{
	if ($values instanceof MessageInterface) {
		$values = $values->insertBefore('(')->insertAfter(')');
		return $message->insertAfter(sprintf('%s in', $field))->insertAfter($values->readMessage())->addValues($values->getValues());
	}
	$q = array_fill(0, sizeof((array) $values), '?');
	return $message->insertAfter(sprintf('%s in (%s)', $field, implode(', ', $q)), MessageInterface::SEPARATOR_OR)->addValues((array) $values);
});

FilterNode::filter('between', function($field, $values, $message)
{
	return $message->insertAfter(sprintf('( %s between ? AND ? )', $field), MessageInterface::SEPARATOR_OR)->addValues((array) $values);
});

FilterNode::filter('eqf', function($field, $value, $message)
{
	return $message->insertAfter(sprintf('%s = %s', $field, $value), MessageInterface::SEPARATOR_OR);
});
