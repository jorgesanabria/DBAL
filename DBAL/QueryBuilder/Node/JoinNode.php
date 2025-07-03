<?php
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;
use DBAL\QueryBuilder\Message;

/**
 * Clase/Interfaz JoinNode
 */
class JoinNode extends NotImplementedNode
{
	const INNER_JOIN = 'INNER JOIN';
	const LEFT_JOIN = 'LEFT JOIN';
	const RIGHT_JOIN = 'RIGHT JOIN';
/** @var mixed */
	protected $isEmpty = false;
/** @var mixed */
	protected $table;
/** @var mixed */
	protected $type;
/** @var mixed */
	protected $on = [];
/**
 * __construct
 * @param mixed $table
 * @param mixed $type
 * @param array $on
 * @return void
 */

        public function __construct($table, $type = JoinNode::INNER_JOIN, array $on = [])
        {
                $this->table = $table;
                $this->type  = $type;
                foreach ($on as $filter) {
                        if ($filter instanceof FilterNode) {
                                $this->on[] = $filter;
                        } else {
                                $this->on[] = new FilterNode($filter);
                        }
                }
        }
/**
 * send
 * @param MessageInterface $message
 * @return mixed
 */

        public function send(MessageInterface $message)
        {
                $msg = new Message($message->type(), sprintf('%s %s', $this->type, $this->table));
                if (sizeof($this->on) > 0) {
                        $onMsg = new Message($message->type());
                        foreach ($this->on as $filter)
                                $onMsg = $filter->send($onMsg);
                        $msg = $msg->join($onMsg->insertBefore('ON'));
                }
                return $message->join($msg);
        }
/**
 * __clone
 * @return mixed
 */

	public function __clone()
	{
		foreach ($this->on as $key=>$node)
			$this->on[$key] = clone $node;
	}
}
