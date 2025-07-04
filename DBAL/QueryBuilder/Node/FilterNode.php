<?php
declare(strict_types=1);
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;
use DBAL\QueryBuilder\Message;
use DBAL\QueryBuilder\FilterOp;

/**
 * Node that represents a group of filtering expressions.
 *
 * Each element of `$parts` is defined as `field__operator => value` where
 * `operator` corresponds to a registered filter name. When `operator` is not
 * specified the `eq` filter will be used.
 *
 * Child FilterNode instances can be appended to create nested conditions. When
 * {@see send()} is executed all conditions and children are converted to SQL
 * snippets joined by the configured logical operator.
 */
class FilterNode extends Node
{
        /**
         * Registered filter callbacks.
         *
         * @var array<string, callable>
         */
        protected static array $filters = [];

        /** @var bool */
        protected bool $isEmpty = false;

        /** @var array<string, mixed> */
        protected array $parts;

        /**
         * Logical separator used when joining this node with others.
         * @var string
         */
        protected string $operator;
        /**
         * Constructor.
         *
         * @param array<string, mixed> $parts  Initial conditions. Keys follow the
         *                                     format "field__filter".
         * @param string               $operator Logical operator used to join this
         *                                     node with others. Defaults to AND.
         */
        public function __construct(array $parts = [], $operator = MessageInterface::SEPARATOR_AND)
        {
                $this->parts = $parts;
                $this->operator = $operator;
        }

        /**
         * Return the raw list of conditions defined for this node.
         *
         * @return array<string, mixed>
         */
        public function getParts()
        {
                return $this->parts;
        }

        /**
         * Add a new condition to this filter node.
         *
         * @param string $name   Condition in the form "field__filter".
         * @param mixed  $value  Value or values passed to the filter callback.
         * @return $this
         */
        public function addCondition($name, $value)
        {
                $this->parts[$name] = $value;
                return $this;
        }
        /**
         * Append a nested FilterNode.
         *
         * Only instances of {@see FilterNode} are accepted as children. The
         * method returns the name assigned to the child as provided by the base
         * {@see Node::appendChild()} implementation.
         *
         * @param NodeInterface $node Child filter node to append.
         * @param string|null   $name Optional node name.
         * @return string|null         The node name or null if not appended.
         */
        public function appendChild(NodeInterface $node, $name = null)
        {
                if ($node instanceof self)
                        $name = parent::appendChild($node, $name);
                return $name;
        }
        /**
         * Build the SQL fragment represented by this node and merge it with the
         * provided message.
         *
         * Conditions defined in `$parts` are processed by the registered filter
         * callbacks. Child nodes are recursively processed. If more than one
         * expression is generated the final fragment is wrapped in parenthesis.
         *
         * @param MessageInterface $message Base message where the fragment will
         *                                   be appended.
         * @return MessageInterface         A new message including the generated
         *                                   SQL and parameter values.
         */
        public function send(MessageInterface $message)
        {
                $msg = new Message($message->type());
                foreach ($this->parts as $condition => $values) {
                        $msg = self::filtering($condition, $values, $msg);
                }
                foreach ($this->allChildren() as $child) {
                        $msg = $child->send($msg);
                }
                if ((count($this->parts) + count($this->allChildren())) > 1)
                        $msg = $msg->insertBefore('(', '')->insertAfter(')', '');
                return ($msg->getLength() > 0)
                        ? $message->join($msg, $this->operator)
                        : $message;
        }

        /**
         * Register a new filter callback.
         *
         * The callback receives the field name, the value provided to the filter
         * and a {@see MessageInterface} where it must append the SQL snippet.
         *
         * @param FilterOp $name     Filter name used in condition keys.
         * @param callable $callback Filter implementation.
         * @return void
         */
        public static function filter(FilterOp $name, callable $callback)
        {
                self::$filters[$name->value] = $callback;
        }

        /**
         * Apply the registered filter on a single condition.
         *
         * @param string           $condition Condition string "field__filter" or
         *                                    just "field".
         * @param mixed            $values    Value or values for the filter.
         * @param MessageInterface $message   Message being built.
         * @return MessageInterface           Message with the filter SQL appended.
         */
        protected static function filtering($condition, $values, MessageInterface $message)
        {
                $parts = explode('__', $condition);
                $op = $parts[1] ?? FilterOp::EQ->value;
                if (isset(self::$filters[$op]))
                {
                        return (self::$filters[$op])($parts[0], $values, $message);
                }
                throw new \RuntimeException(sprintf('The filter "%s" is not exists', $op), 500);
        }
}


FilterNode::filter(FilterOp::EQ, function($field, $value, $message)
{
	return $message->insertAfter(sprintf('%s = ?', $field), MessageInterface::SEPARATOR_OR)->addValues((array) $value);
});

FilterNode::filter(FilterOp::NE, function($field, $value, $message)
{
	return $message->insertAfter(sprintf('%s != ?', $field), MessageInterface::SEPARATOR_OR)->addValues((array) $value);
});

FilterNode::filter(FilterOp::GT, function($field, $value, $message)
{
	return $message->insertAfter(sprintf('%s > ?', $field), MessageInterface::SEPARATOR_OR)->addValues((array) $value);
});

FilterNode::filter(FilterOp::LT, function($field, $value, $message)
{
	return $message->insertAfter(sprintf('%s < ?', $field), MessageInterface::SEPARATOR_OR)->addValues((array) $value);
});

FilterNode::filter(FilterOp::GE, function($field, $value, $message)
{
	return $message->insertAfter(sprintf('%s >= ?', $field), MessageInterface::SEPARATOR_OR)->addValues((array) $value);
});

FilterNode::filter(FilterOp::LE, function($field, $value, $message)
{
	return $message->insertAfter(sprintf('%s <= ?', $field), MessageInterface::SEPARATOR_OR)->addValues((array) $value);
});

FilterNode::filter(FilterOp::IN, function($field, $values, $message)
{
        if ($values instanceof MessageInterface) {
                $values = $values->insertBefore('(', '')->insertAfter(')', '');
                return $message
                        ->insertAfter(sprintf('%s in', $field))
                        ->insertAfter($values->readMessage(), MessageInterface::SEPARATOR_SPACE)
                        ->addValues($values->getValues());
        }
	$q = array_fill(0, sizeof((array) $values), '?');
	return $message->insertAfter(sprintf('%s in (%s)', $field, implode(', ', $q)), MessageInterface::SEPARATOR_OR)->addValues((array) $values);
});

FilterNode::filter(FilterOp::BETWEEN, function($field, $values, $message)
{
        return $message->insertAfter(sprintf('( %s between ? AND ? )', $field), MessageInterface::SEPARATOR_OR)->addValues((array) $values);
});

FilterNode::filter(FilterOp::EQF, function($field, $value, $message)
{
        return $message->insertAfter(sprintf('%s = %s', $field, $value), MessageInterface::SEPARATOR_OR);
});

FilterNode::filter(FilterOp::LIKE, function($field, $value, $msg) {
        return $msg->insertAfter(sprintf('%s LIKE ?', $field), MessageInterface::SEPARATOR_OR)
                   ->addValues([$value]);
});
