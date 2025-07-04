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
use DBAL\QueryBuilder\JoinType;
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
		$clone = clone $this;
               foreach ($tables as $table) {
                       $_table = ($table instanceof TableNode)
                               ? $table
                               : new TableNode($table);
                       $clone->getChild('tables')->appendChild($_table);
               }
		return $clone;
	}
/**
 * join
 * @param JoinType $type
 * @param mixed    $table
 * @param array    $on
 * @return mixed
 */

        protected function join(JoinType $type, $table, array $on = [])
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
                $clone = clone $this;
                $clone->join(JoinType::INNER, $table, $on);
                return $clone;
       }
/**
 * leftJoin
 * @param mixed $table
 * @param mixed $...$on
 * @return mixed
 */

        public function leftJoin($table, ...$on)
        {
                $clone = clone $this;
                $clone->join(JoinType::LEFT, $table, $on);
                return $clone;
       }
/**
 * rightJoin
 * @param mixed $table
 * @param mixed $...$on
 * @return mixed
 */

        public function rightJoin($table, ...$on)
        {
                $clone = clone $this;
                $clone->join(JoinType::RIGHT, $table, $on);
                return $clone;
       }
/**
 * where
 * @param mixed $...$filters
 * @return mixed
 */

        public function where(...$filters)
        {
                $clone = clone $this;
                foreach ($filters as $filter) {
                        if (is_callable($filter)) {
                                $builder = new DynamicFilterBuilder();
                                $filter($builder);
                                $filter = $builder->toNode();
                        }
                        if ($filter instanceof FilterNode) {
                                if (count($filter->getParts()) === 0 && count($filter->allChildren()) > 1) {
                                        foreach ($filter->allChildren() as $child) {
                                                $clone->getChild('where')->appendChild($child);
                                        }
                                } else {
                                        $clone->getChild('where')->appendChild($filter);
                                }
                        } elseif (is_array($filter)) {
                                $clone->getChild('where')->appendChild(new FilterNode($filter));
                        }
                }
                return $clone;
        }
/**
 * having
 * @param array $...$filters
 * @return mixed
 */

	public function having(array ...$filters)
	{
		$clone = clone $this;
		foreach ($filters as $filter)
			$clone->getChild('having')->appendChild(new FilterNode($filter));
		return $clone;
	}
/**
 * group
 * @param mixed $...$fields
 * @return mixed
 */

        public function group(...$fields)
        {
                $clone = clone $this;
                foreach ($fields as $field)
                        $clone->getChild('group')->appendChild(new FieldNode($field));
                return $clone;
        }
/**
 * groupBy
 * @param mixed $...$fields
 * @return mixed
 */

        public function groupBy(...$fields)
        {
                $clone = clone $this;
                return $clone->group(...$fields);
        }
/**
 * order
 * @param mixed $type
 * @param array $fields
 * @return mixed
 */

        public function order($type, array $fields)
        {
                $clone = clone $this;
                foreach ($fields as $field)
                        $clone->getChild('order')->appendChild(new FieldNode(sprintf('%s %s', $field, $type)));
		return $clone;
	}
/**
 * desc
 * @param mixed $...$fields
 * @return mixed
 */

	public function desc(...$fields)
	{
		$clone = clone $this;
		return $clone->order(OrderNode::ORDER_DESC, $fields);
	}
/**
 * asc
 * @param mixed $...$fields
 * @return mixed
 */

	public function asc(...$fields)
	{
		$clone = clone $this;
		return $clone->order(OrderNode::ORDER_ASC, $fields);
	}
/**
 * limit
 * @param mixed $limit
 * @return mixed
 */

	public function limit($limit)
	{
		$clone = clone $this;
		$clone->getChild('limit')->setLimit($limit);
		return $clone;
	}
/**
 * offset
 * @param mixed $offset
 * @return mixed
 */

	public function offset($offset)
	{
		$clone = clone $this;
		$clone->getChild('limit')->setOffset($offset);
		return $clone;
	}
/**
 * buildSelect
 * @param mixed $...$fields
 * @return mixed
 */

	public function buildSelect(...$fields)
	{
		$clone = clone $this;
		$message = new Message(MessageInterface::MESSAGE_TYPE_SELECT);
		if (sizeof($fields) == 0) {
			$message = $clone->send($message);
		} else {
			$old = $clone->removeChild('fields');
			$clone->appendChild(new FieldsNode, 'fields');
			foreach ($fields as $field) {
				if (!$field instanceof FieldNode)
					$field = new FieldNode($field);
				$clone->getChild('fields')->appendChild($field);
			}
			$message = $clone->send($message);
			$clone->removeChild('fields');
			$clone->appendChild($old, 'fields');
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
                $clone = clone $this;
                $message = new Message(MessageInterface::MESSAGE_TYPE_INSERT);
                $clone->getChild('change')->setFields($fields);
                $message = $clone->send($message);
                return $message;
        }
/**
 * buildBulkInsert
 * @param array $rows
 * @return mixed
 */

        public function buildBulkInsert(array $rows)
        {
                $clone = clone $this;
                $message = new Message(MessageInterface::MESSAGE_TYPE_INSERT);
                $clone->getChild('change')->setRows($rows);
                $message = $clone->send($message);
                return $message;
        }
/**
 * buildUpdate
 * @param array $fields
 * @return mixed
 */

        public function buildUpdate(array $fields)
        {
                $clone = clone $this;
                $message = new Message(MessageInterface::MESSAGE_TYPE_UPDATE);
                $clone->getChild('change')->setFields($fields);
		$message = $clone->send($message);
		return $message;
	}
/**
 * buildDelete
 * @return mixed
 */

	public function buildDelete()
	{
		$clone = clone $this;
		$message = new Message(MessageInterface::MESSAGE_TYPE_DELETE);
		$message = $clone->send($message);
		return $message;
	}
}
