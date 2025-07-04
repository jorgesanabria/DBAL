<?php
declare(strict_types=1);
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;
use DBAL\QueryBuilder\Message;
use DBAL\QueryBuilder\Node\NodeInterface;

/**
 * Node responsible for building a SQL `GROUP BY` clause.
 *
 * Child nodes must be instances of {@see FieldNode} representing the fields
 * used for grouping. When {@see send()} is executed the node concatenates the
 * SQL produced by its children and prefixes it with `GROUP BY`.
 */
class GroupNode extends Node
{
        /** @var bool */
        protected bool $isEmpty = false;
        /**
         * Generate the GROUP BY SQL fragment and append it to the given message.
         *
         * @param MessageInterface $message Message being built.
         * @return MessageInterface         Message with the GROUP BY clause appended.
         */
        public function send(MessageInterface $message)
        {
                $msg = new Message($message->type());
                foreach ($this->allChildren() as $child) {
                        $msg = $child->send($msg);
                }
                return ($msg->getLength() > 0)
                        ? $message->join($msg->insertBefore('GROUP BY'))
                        : $message;
        }

        /**
         * Append a grouping field to this node.
         *
         * Only {@see FieldNode} instances are allowed as children.
         *
         * @param NodeInterface $node Field node to append.
         * @param string|null   $name Optional node name.
         * @return string|null         The assigned node name or null when skipped.
         */
        public function appendChild(NodeInterface $node, $name = null)
        {
                if ($node instanceof FieldNode) {
                        $name = parent::appendChild($node, $name);
                }
                return $name;
        }
}
