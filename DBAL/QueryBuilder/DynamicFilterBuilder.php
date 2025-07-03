<?php
namespace DBAL\QueryBuilder;

use DBAL\QueryBuilder\MessageInterface;
use DBAL\QueryBuilder\Node\FilterNode;

class DynamicFilterBuilder
{
       protected $stack = [];
       protected $nextOperator = MessageInterface::SEPARATOR_AND;

       public function __construct()
       {
               $root = new FilterNode();
               $this->stack[] = $root;
       }

       protected function current()
       {
               return $this->stack[count($this->stack) - 1];
       }

       public function __call($name, $arguments)
       {
               $node = new FilterNode([
                       $name => (count($arguments) <= 1) ? ($arguments[0] ?? null) : $arguments
               ], $this->nextOperator);
               $this->nextOperator = MessageInterface::SEPARATOR_AND;
               $this->current()->appendChild($node);
               return $this;
       }

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

       public function andGroup(callable $callback)
       {
               return $this->group($callback, MessageInterface::SEPARATOR_AND);
       }

       public function orGroup(callable $callback)
       {
               return $this->group($callback, MessageInterface::SEPARATOR_OR);
       }

       public function andNext()
       {
               $this->nextOperator = MessageInterface::SEPARATOR_AND;
               return $this;
       }

       public function orNext()
       {
               $this->nextOperator = MessageInterface::SEPARATOR_OR;
               return $this;
       }

       public function toNode()
       {
               return $this->stack[0];
       }

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
