<?php
declare(strict_types=1);
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;
use DBAL\QueryBuilder\Message;
use DBAL\QueryBuilder\JoinType;

/**
 * Node that builds SQL JOIN clauses.
 *
 * The node stores the join type (INNER, LEFT, RIGHT), the table expression and
 * a list of filter nodes used in the `ON` part of the JOIN. When {@see send()}
 * is called it generates a SQL fragment like `LEFT JOIN table ON ...` and
 * appends it to the provided message.
 */
class JoinNode extends NotImplementedNode
{
        /** @var bool */
        protected bool $isEmpty = false;

        /** @var string Table expression or name to join */
        protected string $table;

        /** @var JoinType Join type */
        protected JoinType $type;

        /** @var FilterNode[] Conditions used in the ON clause */
        protected array $on = [];
        /**
         * Constructor.
         *
         * @param string                    $table Table expression to join.
         * @param JoinType                  $type  Join type, defaults to JoinType::INNER.
         * @param array<int,FilterNode|array> $on  List of conditions for the ON clause.
         */
        public function __construct($table, JoinType $type = JoinType::INNER, array $on = [])
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
         * Build the JOIN clause and append it to the provided message.
         *
         * All `ON` conditions are processed using their respective
         * {@see FilterNode} instances.
         *
         * @param MessageInterface $message Base message for building.
         * @return MessageInterface         Message with the JOIN clause appended.
         */
        public function send(MessageInterface $message)
        {
                $msg = new Message($message->type(), sprintf('%s %s', $this->type->value, $this->table));
                if (sizeof($this->on) > 0) {
                        $onMsg = new Message($message->type());
                        foreach ($this->on as $filter) {
                                $onMsg = $filter->send($onMsg);
                        }
                        $msg = $msg->join($onMsg->insertBefore('ON'));
                }
                return $message->join($msg);
        }
        /**
         * Deep clone the ON conditions when the node is cloned.
         */
        public function __clone()
        {
                foreach ($this->on as $key => $node) {
                        $this->on[$key] = clone $node;
                }
        }
}
