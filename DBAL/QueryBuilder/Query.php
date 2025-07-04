<?php
declare(strict_types=1);
namespace DBAL\QueryBuilder;

use DBAL\ResultIterator;
use DBAL\QueryBuilder\MessageInterface;
use DBAL\QueryBuilder\Message;
use DBAL\QueryBuilder\Node\QueryNode;
use DBAL\QueryBuilder\Node\TablesNode;
use DBAL\QueryBuilder\Node\TableNode;
use DBAL\QueryBuilder\Node\FieldsNode;
use DBAL\QueryBuilder\Node\FieldNode;
use DBAL\QueryBuilder\Node\JoinsNode;
use DBAL\QueryBuilder\Node\JoinNode;
use DBAL\QueryBuilder\Node\WhereNode;
use DBAL\QueryBuilder\Node\HavingNode;
use DBAL\QueryBuilder\Node\FilterNode;
use DBAL\QueryBuilder\Node\GroupNode;
use DBAL\QueryBuilder\Node\OrderNode;
use DBAL\QueryBuilder\Node\LimitNode;
use DBAL\QueryBuilder\Node\ChangeNode;
use DBAL\QueryBuilder\DynamicFilterBuilder;

/**
 * Clase/Interfaz Query
 */
class Query extends QueryNode
{
/**
 * from
 * @param mixed $...$tables
 * @return mixed
 */

	public function from(...$tables)
	{
		$clon = clone $this;
               foreach ($tables as $table) {
                       $_table = ($table instanceof TableNode)
                               ? $table
                               : new TableNode($table);
                       $clon->getChild('tables')->appendChild($_table);
               }
		return $clon;
	}
/**
 * join
 * @param mixed $type
 * @param mixed $table
 * @param array $on
 * @return mixed
 */

        protected function join($type, $table, array $on = [])
        {
                $conditions = [];
                foreach ($on as $filter) {
                        if (is_callable($filter)) {
                                $builder = new DynamicFilterBuilder();
                                $filter($builder);
                                $conditions[] = $builder->toNode();
                        } elseif ($filter instanceof FilterNode) {
                                $conditions[] = $filter;
                        } elseif (is_array($filter)) {
                                $conditions[] = new FilterNode($filter);
                        }
                }
                $this->getChild('joins')->appendChild(new JoinNode($table, $type, $conditions));
        }
/**
 * innerJoin
 * @param mixed $table
 * @param mixed $...$on
 * @return mixed
 */

        public function innerJoin($table, ...$on)
        {
                $clon = clone $this;
                $clon->join(JoinNode::INNER_JOIN, $table, $on);
                return $clon;
        }
/**
 * leftJoin
 * @param mixed $table
 * @param mixed $...$on
 * @return mixed
 */

        public function leftJoin($table, ...$on)
        {
                $clon = clone $this;
                $clon->join(JoinNode::LEFT_JOIN, $table, $on);
                return $clon;
        }
/**
 * rightJoin
 * @param mixed $table
 * @param mixed $...$on
 * @return mixed
 */

        public function rightJoin($table, ...$on)
        {
                $clon = clone $this;
                $clon->join(JoinNode::RIGHT_JOIN, $table, $on);
                return $clon;
        }
/**
 * where
 * @param mixed $...$filters
 * @return mixed
 */

        public function where(...$filters)
        {
                $clon = clone $this;
                foreach ($filters as $filter) {
                        if (is_callable($filter)) {
                                $builder = new DynamicFilterBuilder();
                                $filter($builder);
                                $filter = $builder->toNode();
                        }
                        if ($filter instanceof FilterNode) {
                                if (count($filter->getParts()) === 0 && count($filter->allChildren()) > 1) {
                                        foreach ($filter->allChildren() as $child) {
                                                $clon->getChild('where')->appendChild($child);
                                        }
                                } else {
                                        $clon->getChild('where')->appendChild($filter);
                                }
                        } elseif (is_array($filter)) {
                                $clon->getChild('where')->appendChild(new FilterNode($filter));
                        }
                }
                return $clon;
        }
/**
 * having
 * @param array $...$filters
 * @return mixed
 */

	public function having(array ...$filters)
	{
		$clon = clone $this;
		foreach ($filters as $filter)
			$clon->getChild('having')->appendChild(new FilterNode($filter));
		return $clon;
	}
/**
 * group
 * @param mixed $...$fields
 * @return mixed
 */

        public function group(...$fields)
        {
                $clon = clone $this;
                foreach ($fields as $field)
                        $clon->getChild('group')->appendChild(new FieldNode($field));
                return $clon;
        }
/**
 * groupBy
 * @param mixed $...$fields
 * @return mixed
 */

        public function groupBy(...$fields)
        {
                $clon = clone $this;
                return $clon->group(...$fields);
        }
/**
 * order
 * @param mixed $type
 * @param array $fields
 * @return mixed
 */

        public function order($type, array $fields)
        {
                $clon = clone $this;
                foreach ($fields as $field)
                        $clon->getChild('order')->appendChild(new FieldNode(sprintf('%s %s', $field, $type)));
		return $clon;
	}
/**
 * desc
 * @param mixed $...$fields
 * @return mixed
 */

	public function desc(...$fields)
	{
		$clon = clone $this;
		return $clon->order(OrderNode::ORDER_DESC, $fields);
	}
/**
 * asc
 * @param mixed $...$fields
 * @return mixed
 */

	public function asc(...$fields)
	{
		$clon = clone $this;
		return $clon->order(OrderNode::ORDER_ASC, $fields);
	}
/**
 * limit
 * @param mixed $limit
 * @return mixed
 */

	public function limit($limit)
	{
		$clon = clone $this;
		$clon->getChild('limit')->setLimit($limit);
		return $clon;
	}
/**
 * offset
 * @param mixed $offset
 * @return mixed
 */

	public function offset($offset)
	{
		$clon = clone $this;
		$clon->getChild('limit')->setOffset($offset);
		return $clon;
	}
/**
 * buildSelect
 * @param mixed $...$fields
 * @return mixed
 */

	public function buildSelect(...$fields)
	{
		$clon = clone $this;
		$message = new Message(MessageInterface::MESSAGE_TYPE_SELECT);
		if (sizeof($fields) == 0) {
			$message = $clon->send($message);
		} else {
			$old = $clon->removeChild('fields');
			$clon->appendChild(new FieldsNode, 'fields');
			foreach ($fields as $field) {
				if (!$field instanceof FieldNode)
					$field = new FieldNode($field);
				$clon->getChild('fields')->appendChild($field);
			}
			$message = $clon->send($message);
			$clon->removeChild('fields');
			$clon->appendChild($old, 'fields');
		}
		return $message;
	}
/**
 * buildInsert
 * @param array $fields
 * @return mixed
 */

        public function buildInsert(array $fields)
        {
                $clon = clone $this;
                $message = new Message(MessageInterface::MESSAGE_TYPE_INSERT);
                $clon->getChild('change')->setFields($fields);
                $message = $clon->send($message);
                return $message;
        }
/**
 * buildBulkInsert
 * @param array $rows
 * @return mixed
 */

        public function buildBulkInsert(array $rows)
        {
                $clon = clone $this;
                $message = new Message(MessageInterface::MESSAGE_TYPE_INSERT);
                $clon->getChild('change')->setRows($rows);
                $message = $clon->send($message);
                return $message;
        }
/**
 * buildUpdate
 * @param array $fields
 * @return mixed
 */

        public function buildUpdate(array $fields)
        {
                $clon = clone $this;
                $message = new Message(MessageInterface::MESSAGE_TYPE_UPDATE);
                $clon->getChild('change')->setFields($fields);
		$message = $clon->send($message);
		return $message;
	}
/**
 * buildDelete
 * @return mixed
 */

	public function buildDelete()
	{
		$clon = clone $this;
		$message = new Message(MessageInterface::MESSAGE_TYPE_DELETE);
		$message = $clon->send($message);
		return $message;
	}
}
