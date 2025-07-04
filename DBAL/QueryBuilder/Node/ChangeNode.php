<?php
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;
use DBAL\QueryBuilder\Message;

/**
 * Clase/Interfaz ChangeNode
 */
class ChangeNode extends NotImplementedNode
{
/** @var mixed */
        protected bool $isEmpty = false;
        protected array $fields = [];
        protected ?array $rows = null;
/**
 * setFields
 * @param array $fields
 * @return mixed
 */

        public function setFields(array $fields)
        {
                $this->fields = $fields;
        }
/**
 * setRows
 * @param array $rows
 * @return mixed
 */

        public function setRows(array $rows)
        {
                $this->rows = $rows;
        }
/**
 * send
 * @param MessageInterface $message
 * @return mixed
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
