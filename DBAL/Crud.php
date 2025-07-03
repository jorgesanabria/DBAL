<?php
namespace DBAL;

use DBAL\QueryBuilder\Query;
use DBAL\QueryBuilder\MessageInterface;

class Crud extends Query
{
        protected $connection;
        protected $mappers = [];
        protected $middlewares = [];
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
        protected function runMiddlewares(MessageInterface $message)
        {
                foreach ($this->middlewares as $mw)
                        $mw($message);
        }
        public function select(...$fields)
        {
                $message = $this->buildSelect(...$fields);
                return new ResultIterator($this->connection, $message, $this->mappers, $this->middlewares);
        }
        public function insert(array $fields)
        {
                $message = $this->buildInsert($fields);
                $this->runMiddlewares($message);
                $stm = $this->connection->prepare($message->readMessage());
                $stm->execute($message->getValues());
                return $this->connection->lastInsertId();
        }
        public function update(array $fields)
        {
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
}
