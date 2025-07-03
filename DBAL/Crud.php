<?php
namespace DBAL;

use DBAL\QueryBuilder\Query;
use DBAL\QueryBuilder\MessageInterface;
use DBAL\RelationDefinition;
use Generator;

class Crud extends Query
{
        protected $connection;
        protected $mappers = [];
        protected $middlewares = [];
        protected $tables = [];
        protected $with = [];
        public function __construct(\PDO $connection)
        {
                $this->connection = $connection;
                parent::__construct();
        }
        public function map(callable $callback)
        {
                $clon = clone $this;
                $clon->mappers[] = $callback;
                return $clon;
        }
        public function withMiddleware(callable $mw)
        {
                $clon = clone $this;
                $clon->middlewares[] = $mw;
                return $clon;
        }

        public function from(...$tables)
        {
                $clon = parent::from(...$tables);
                foreach ($tables as $table) {
                        $clon->tables[] = $table;
                }
                return $clon;
        }

        public function with(...$relations)
        {
                $clon = clone $this;
                $defs = $clon->collectRelations($clon->primaryTable());
                foreach ($relations as $rel) {
                        if (!isset($defs[$rel])) {
                                continue;
                        }
                        $def = $defs[$rel];
                        if ($def instanceof RelationDefinition) {
                                $conds = [];
                                foreach ($def->getConditions() as $c) {
                                        if ($c[1] === '=') {
                                                $conds[] = [$c[0] . '__eqf' => $c[2]];
                                        }
                                }
                                $clon = $clon->leftJoin($def->getTable(), ...$conds);
                        } else {
                                $join = $def['on'];
                                if (($def['joinType'] ?? 'left') === 'inner') {
                                        $clon = $clon->innerJoin($def['table'], $join);
                                } else {
                                        $clon = $clon->leftJoin($def['table'], $join);
                                }
                        }
                        $clon->with[] = $rel;
                }
                return $clon;
        }

        private function primaryTable()
        {
                return $this->tables[0] ?? '';
        }
        protected function runMiddlewares(MessageInterface $message)
        {
                foreach ($this->middlewares as $mw)
                        $mw($message);
        }
        private function collectRelations($table)
        {
                $relations = [];
                foreach ($this->middlewares as $mw) {
                        if (is_object($mw) && method_exists($mw, 'getRelations')) {
                                $relations += $mw->getRelations($table);
                        }
                }
                return $relations;
        }
        public function select(...$fields)
        {
                $message = $this->buildSelect(...$fields);
                $relations = $this->collectRelations($this->primaryTable());
                return new ResultIterator(
                        $this->connection,
                        $message,
                        $this->mappers,
                        $this->middlewares,
                        $relations,
                        $this->with
                );
        }

        public function stream(...$args)
        {
                $callback = null;
                if (isset($args[0]) && is_callable($args[0])) {
                        $callback = array_shift($args);
                }
                $message = $this->buildSelect(...$args);
                $relations = $this->collectRelations($this->primaryTable());
                $generator = new ResultGenerator(
                        $this->connection,
                        $message,
                        $this->mappers,
                        $this->middlewares,
                        $relations,
                        $this->with
                );
                return $generator->getIterator($callback);
        }
        public function insert(array $fields)
        {
                foreach ($this->middlewares as $mw) {
                        if ($mw instanceof EntityValidationInterface) {
                                $mw->beforeInsert($this->primaryTable(), $fields);
                        }
                }
                $message = $this->buildInsert($fields);
                $this->runMiddlewares($message);
                $stm = $this->connection->prepare($message->readMessage());
                $stm->execute($message->getValues());
                return $this->connection->lastInsertId();
        }
        public function bulkInsert(array $rows)
        {
                foreach ($this->middlewares as $mw) {
                        if ($mw instanceof EntityValidationInterface) {
                                foreach ($rows as $row) {
                                        $mw->beforeInsert($this->primaryTable(), $row);
                                }
                        }
                }
                $message = $this->buildBulkInsert($rows);
                $this->runMiddlewares($message);
                $stm = $this->connection->prepare($message->readMessage());
                $stm->execute($message->getValues());
                return $stm->rowCount();
        }
        public function update(array $fields)
        {
                foreach ($this->middlewares as $mw) {
                        if ($mw instanceof EntityValidationInterface) {
                                $mw->beforeUpdate($this->primaryTable(), $fields);
                        }
                }
                $message = $this->buildUpdate($fields);
                $this->runMiddlewares($message);
                $stm = $this->connection->prepare($message->readMessage());
                $stm->execute($message->getValues());
                return $stm->rowCount();
        }
       public function delete()
       {
               $message = $this->buildDelete();
               $this->runMiddlewares($message);
               $stm = $this->connection->prepare($message->readMessage());
               $stm->execute($message->getValues());
               return $stm->rowCount();
       }

       public function __call($name, $arguments)
       {
               foreach ($this->middlewares as $mw) {
                       if (is_object($mw) && is_callable([$mw, $name])) {
                               if ($mw instanceof CrudAwareMiddlewareInterface) {
                                       array_unshift($arguments, $this);
                               }
                               return $mw->$name(...$arguments);
                       }
               }
               throw new \BadMethodCallException(sprintf('Method %s does not exist', $name));
       }
}
