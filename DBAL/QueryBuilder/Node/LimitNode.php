<?php
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;

/**
 * Node that builds `LIMIT` and `OFFSET` clauses for a query.
 */
class LimitNode extends NotImplementedNode
{
        /** @var bool */
        protected bool $isEmpty = false;

        /** @var int|null */
        protected ?int $limit = null;

        /** @var int|null */
        protected ?int $offset = null;
        /**
         * Define the maximum number of rows to return.
         */
        public function setLimit($limit)
        {
                $this->limit = $limit;
        }
        /**
         * Define the starting offset of the result set.
         */
        public function setOffset($offset)
        {
                $this->offset = $offset;
        }
        /**
         * Append LIMIT/OFFSET to the query message when defined.
         */
        public function send(MessageInterface $message)
	{
		$msg = $message;
		if ($this->limit === null && $this->offset === null)
			$msg = $message;
		else if ($this->limit !== null && $this->offset === null)
			$msg = $message->addValues([$this->limit])->insertAfter('LIMIT ?');
		else if ($this->limit === null && $this->offset !== null)
			$msg = $message->addValues([$this->offset])->insertAfter('LIMIT -1 OFFSET ?');
		else if ($this->limit !== null && $this->offset !== null)
			$msg = $message->addValues([$this->limit, $this->offset])->insertAfter('LIMIT ? OFFSET ?');
		$this->limit = $this->offset = null;
		return $msg;
	}
}
