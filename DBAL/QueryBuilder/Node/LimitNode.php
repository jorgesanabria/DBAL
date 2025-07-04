<?php
declare(strict_types=1);
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;
use DBAL\Platform\PlatformInterface;

/**
 * Node that builds `LIMIT` and `OFFSET` clauses for a query.
 */
class LimitNode extends NotImplementedNode
{
        /** @var bool */
        protected bool $isEmpty = false;

        public function __construct(private PlatformInterface $platform)
        {
        }

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
                $msg = $this->platform->applyLimitOffset($message, $this->limit, $this->offset);
                $this->limit = $this->offset = null;
                return $msg;
        }
}
