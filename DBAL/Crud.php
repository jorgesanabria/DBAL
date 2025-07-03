<?php
namespace DBAL;

use DBAL\QueryBuilder\Query;
use DBAL\QueryBuilder\MessageInterface;

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

        public function with(string ...$relations)
        {
                $clon = clone $this;
                foreach ($relations as $r)
                        $clon->with[] = $r;
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

        private function primaryTable()
        {
                return $this->tables[0] ?? '';
        }
        protected function runMiddlewares(MessageInterface $message)
        {
                foreach ($this->middlewares as $mw)
                        $mw($message);
        }

        private function resolveRelation($name)
        {
                foreach ($this->middlewares as $mw) {
                        if (is_object($mw) && method_exists($mw, 'getRelation')) {
                                $rel = $mw->getRelation($this->primaryTable(), $name);
                                if ($rel) return $rel;
                        }
                }
                return null;
        }
        public function select(...$fields)
        {
                $query = $this;
                foreach ($this->with as $relName) {
                        $rel = $this->resolveRelation($relName);
                        if ($rel) {
                                $conds = [];
                                foreach ($rel->getConditions() as $c) {
                                        if ($c[1] === '=') {
                                                $conds[] = [$c[0].'__eqf' => $c[2]];
                                        }
                                }
                                if (!empty($conds)) {
                                        $query = $query->leftJoin($rel->getTable(), ...$conds);
                                }
                        }
                }

                $message = $query->buildSelect(...$fields);
                return new ResultIterator($this->connection, $message, $this->mappers, $this->middlewares);
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
                               return $mw->$name(...$arguments);
                       }
               }
               throw new \BadMethodCallException(sprintf('Method %s does not exist', $name));
       }
}
