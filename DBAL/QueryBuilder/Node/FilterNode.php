<?php
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;
use DBAL\QueryBuilder\Message;

/**
 * Clase/Interfaz FilterNode
 */
class FilterNode extends Node
{
        protected static array $filters = [];
        protected bool $isEmpty = false;
        protected array $parts;
        protected string $operator;
/**
 * __construct
 * @param array $parts
 * @param mixed $operator
 * @return void
 */

        public function __construct(array $parts = [], $operator = MessageInterface::SEPARATOR_AND)
        {
                $this->parts = $parts;
                $this->operator = $operator;
        }
/**
 * getParts
 * @return mixed
 */

        public function getParts()
        {
                return $this->parts;
        }
/**
 * addCondition
 * @param mixed $name
 * @param mixed $value
 * @return mixed
 */

        public function addCondition($name, $value)
        {
                $this->parts[$name] = $value;
                return $this;
        }
/**
 * appendChild
 * @param NodeInterface $node
 * @param mixed $name
 * @return mixed
 */

        public function appendChild(NodeInterface $node, $name = null)
        {
                if ($node instanceof self)
                        $name = parent::appendChild($node, $name);
                return $name;
        }
/**
 * send
 * @param MessageInterface $message
 * @return mixed
 */

        public function send(MessageInterface $message)
        {
                $msg = new Message($message->type());
                foreach ($this->parts as $condition=>$values) {
                        $msg = self::filtering($condition, $values, $msg);
                }
                foreach ($this->allChildren() as $child) {
                        $msg = $child->send($msg);
                }
                if ((count($this->parts) + count($this->allChildren())) > 1)
                        $msg = $msg->insertBefore('(')->insertAfter(')');
                return ($msg->getLength() > 0)? $message->join($msg, $this->operator) : $message;
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
