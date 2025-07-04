<?php
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;
use DBAL\QueryBuilder\Message;

/**
 * Node used for INSERT and UPDATE statements.
 *
 * It stores field/value pairs or multiple rows of values depending on the
 * operation being built.
 */
class ChangeNode extends NotImplementedNode
{
        /** @var bool */
        protected bool $isEmpty = false;

        /** @var array<string,mixed> */
        protected array $fields = [];

        /** @var array<int,array<string,mixed>>|null */
        protected ?array $rows = null;
        /**
         * Define field/value pairs for INSERT or UPDATE operations.
         *
         * @param array<string,mixed> $fields
         */
        public function setFields(array $fields)
        {
                $this->fields = $fields;
        }
        /**
         * Define multiple rows for bulk INSERT operations.
         *
         * @param array<int,array<string,mixed>> $rows
         */
        public function setRows(array $rows)
        {
                $this->rows = $rows;
        }
        /**
         * Build the INSERT or UPDATE fragment based on the message type.
         */
        public function send(MessageInterface $message)
        {
                $msg = null;
                if ($message->type() == MessageInterface::MESSAGE_TYPE_INSERT) {
                        if ($this->rows !== null) {
                                $first = $this->rows[0] ?? [];
                                $cols = array_keys($first);
                                $placeholders = '(' . implode(', ', array_fill(0, count($cols), '?')) . ')';
                                $values = [];
                                $q = [];
                                foreach ($this->rows as $row) {
                                        $q[] = $placeholders;
                                        $values = array_merge($values, array_values($row));
                                }
                                $msg = new Message(MessageInterface::MESSAGE_TYPE_INSERT, 'VALUES', $values);
                                $fields = sprintf('(%s)', implode(', ', $cols));
                                $msg = $msg->insertBefore($fields);
                                $msg = $msg->insertAfter(implode(', ', $q));
                        } else {
                                $msg = new Message(MessageInterface::MESSAGE_TYPE_INSERT, 'VALUES', array_values($this->fields));
                                $fields = sprintf('(%s)', implode(', ', array_keys($this->fields)));
                                $msg = $msg->insertBefore($fields);
                                $q = sprintf('(%s)', implode(', ', array_fill(0, sizeof($this->fields), '?')));
                                $msg = $msg->insertAfter($q);
                        }
                } else if ($message->type() == MessageInterface::MESSAGE_TYPE_UPDATE) {
                        $msg = new Message(MessageInterface::MESSAGE_TYPE_UPDATE, 'SET', array_values($this->fields));
                        $fields = implode(', ',
                                array_map(
					function($field)
					{
						return sprintf('%s = ?', $field);
					},
					array_keys($this->fields)
				)
			);
                        $msg = $msg->insertAfter($fields);
                }
                $this->fields = [];
                $this->rows = null;
                return ($msg != null)? $message->insertAfter($msg->readMessage())->addValues($msg->getValues()) : $message;
        }
}
