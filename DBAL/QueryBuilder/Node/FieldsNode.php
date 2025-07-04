<?php
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;
use DBAL\QueryBuilder\Message;
use DBAL\QueryBuilder\Node\NodeInterface;

/**
 * Node responsible for generating the list of fields in a SELECT statement.
 *
 * It accepts {@see FieldNode} children and when empty defaults to `SELECT *`.
 */
class FieldsNode extends Node
{
        /** @var bool */
        protected bool $isEmpty = false;
        /**
         * Build the field list for a SELECT query.
         *
         * @param MessageInterface $message Base message.
         * @return MessageInterface         Message with the field list appended.
         */
        public function send(MessageInterface $message)
        {
                $msg = new Message($message->type());
                foreach ($this->allChildren() as $child) {
                        $msg = $child->send($msg);
                }
                return ($msg->getLength() > 0)? $message->join($msg->insertBefore('SELECT')) : $message->join($msg->insertAfter('SELECT *'));
        }
        /**
         * Append a field to the SELECT list.
         *
         * @param NodeInterface $node Field node.
         * @param string|null   $name Optional node name.
         * @return string|null
         */
        public function appendChild(NodeInterface $node, $name = null)
        {
                if ($node instanceof FieldNode) {
                        $name = parent::appendChild($node, $name);
                }
                return $name;
	}
}
