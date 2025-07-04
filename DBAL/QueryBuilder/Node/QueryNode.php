<?php
declare(strict_types=1);
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;
use DBAL\QueryBuilder\Message;
use DBAL\QueryBuilder\NodeInterface;
use DBAL\Platform\PlatformInterface;
use DBAL\Platform\SqlitePlatform;

/**
 * Root node for building complete SQL queries.
 *
 * It aggregates sub-nodes for each part of a query such as tables, fields,
 * joins, where conditions, etc.
 */
class QueryNode extends Node
{
        /** @var bool */
        protected bool $isEmpty = false;

        public function __construct(private ?PlatformInterface $platform = null)
        {
                $this->platform = $this->platform ?? new SqlitePlatform();
                $this->appendChild(new TablesNode, 'tables');
                $this->appendChild(new FieldsNode, 'fields');
                $this->appendChild(new JoinsNode, 'joins');
                $this->appendChild(new WhereNode, 'where');
                $this->appendChild(new HavingNode, 'having');
                $this->appendChild(new GroupNode, 'group');
                $this->appendChild(new OrderNode, 'order');
                $this->appendChild(new LimitNode($this->platform), 'limit');
                $this->appendChild(new ChangeNode, 'change');
        }
        /**
         * Generate the SQL for the configured query type.
         */
        public function send(MessageInterface $message)
        {
                return self::build($this, $message);
        }
        /**
         * Execute the node pipeline according to the message type.
         */
        public static function build(QueryNode $query, MessageInterface $message)
        {
                $use = [];
		if ($message->type() == MessageInterface::MESSAGE_TYPE_SELECT)
			$use = ['fields', 'tables', 'joins', 'where', 'group', 'having', 'order', 'limit'];
		else if ($message->type() == MessageInterface::MESSAGE_TYPE_INSERT)
			$use = ['tables', 'change'];
		else if ($message->type() == MessageInterface::MESSAGE_TYPE_UPDATE)
			$use = ['tables', 'change', 'where', 'order', 'limit'];
		else if ($message->type() == MessageInterface::MESSAGE_TYPE_DELETE)
			$use = ['tables', 'where', 'order', 'limit'];
		foreach ($use as $node)
			$message = $query->getChild($node)->send($message);
		return $message;
	}
}

