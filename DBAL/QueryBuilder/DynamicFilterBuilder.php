<?php
declare(strict_types=1);
namespace DBAL\QueryBuilder;

use DBAL\QueryBuilder\MessageInterface;
use DBAL\QueryBuilder\Node\FilterNode;

/**
 * Clase/Interfaz DynamicFilterBuilder
 */
class DynamicFilterBuilder
{
       protected array $stack = [];
       protected string $nextOperator = MessageInterface::SEPARATOR_AND;

/**
 * __construct
 * @return void
 */

       public function __construct()
       {
               $root = new FilterNode();
               $this->stack[] = $root;
       }

/**
 * current
 * @return FilterNode
 */

       protected function current()
       {
               return $this->stack[count($this->stack) - 1];
       }

/**
 * __call
 * @param string $name
 * @param array $arguments
 * @return self
 */

       public function __call($name, $arguments)
       {
               $node = new FilterNode([
                       $name => (count($arguments) <= 1) ? ($arguments[0] ?? null) : $arguments
               ], $this->nextOperator);
               $this->nextOperator = MessageInterface::SEPARATOR_AND;
               $this->current()->appendChild($node);
               return $this;
       }

/**
 * group
 * @param callable $callback
 * @param string $operator
 * @return self
 */

       protected function group(callable $callback, $operator)
       {
               $node = new FilterNode([], $operator);
               $this->nextOperator = MessageInterface::SEPARATOR_AND;
               $this->current()->appendChild($node);
               $this->stack[] = $node;
               $callback($this);
               array_pop($this->stack);
               return $this;
       }

/**
 * andGroup
 * @param callable $callback
 * @return self
 */

       public function andGroup(callable $callback)
       {
               return $this->group($callback, MessageInterface::SEPARATOR_AND);
       }

/**
 * orGroup
 * @param callable $callback
 * @return self
 */

       public function orGroup(callable $callback)
       {
               return $this->group($callback, MessageInterface::SEPARATOR_OR);
       }

/**
 * andNext
 * @return self
 */

       public function andNext()
       {
               $this->nextOperator = MessageInterface::SEPARATOR_AND;
               return $this;
       }

/**
 * orNext
 * @return self
 */

       public function orNext()
       {
               $this->nextOperator = MessageInterface::SEPARATOR_OR;
               return $this;
       }

/**
 * toNode
 * @return FilterNode
 */

       public function toNode()
       {
               return $this->stack[0];
       }

/**
 * toArray
 * @return array
 */

       public function toArray()
       {
               $parts = [];
               foreach ($this->stack[0]->allChildren() as $child) {
                       $childParts = $child->getParts();
                       if (count($childParts) === 1 && count($child->allChildren()) === 0) {
                               $parts += $childParts;
                       }
               }
               return $parts;
       }
}
