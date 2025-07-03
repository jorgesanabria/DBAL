<?php
namespace DBAL\QueryBuilder;

class DynamicFilterBuilder
{
       protected $filters = [];

       public function __call($name, $arguments)
       {
               $this->filters[$name] = (count($arguments) <= 1) ? ($arguments[0] ?? null) : $arguments;
               return $this;
       }

       public function toArray()
       {
               return $this->filters;
       }
}
