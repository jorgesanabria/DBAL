<?php
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;

/**
 * Node representing a single field or expression used in SELECT, GROUP BY or
 * ORDER BY clauses.
 */
class FieldNode extends NotImplementedNode
{
        /** @var bool */
        protected bool $isEmpty = false;

        /** @var mixed Field or expression */
        protected mixed $field;
        /**
         * @param mixed $field Field name or expression.
         */
        public function __construct($field)
        {
                $this->field = $field;
        }
        /**
         * Append the field to the given message.
         */
        public function send(MessageInterface $message)
        {
                return $message->insertAfter($this->field, MessageInterface::SEPARATOR_COMMA);
        }
}
