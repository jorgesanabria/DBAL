<?php
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;

class ResultIterator implements \Iterator, \JsonSerializable
{
        protected $pdo;
        protected $message;
        protected $result;
        protected $i;
        protected $stm;
        protected $mappers;
        protected $middlewares;
        protected $relations;
        protected $eagerRelations;
        public function __construct(\PDO $pdo, MessageInterface $message, array $mappers = [], array $middlewares = [], array $relations = [], array $eagerRelations = [])
        {
                $this->pdo = $pdo;
                $this->message = $message;
                $this->mappers = $mappers;
                $this->middlewares = $middlewares;
                $this->relations = $relations;
                $this->eagerRelations = $eagerRelations;
        }
        public function rewind()
        {
                foreach ($this->middlewares as $mw)
                        $mw($this->message);
                $this->stm = $this->pdo->prepare($this->message->readMessage());
                $this->stm->execute($this->message->getValues());
                $this->result = $this->stm->fetch();
                $this->i = 0;
        }
	public function valid()
	{
		return $this->result !== false;
	}
	public function key()
	{
		return $this->i;
	}
        public function current()
        {
                $result = $this->result;
                foreach ($this->mappers as $mapper)
                        $result = call_user_func_array($mapper, [$result]);

                foreach ($this->relations as $name => $rel) {
                        if (!in_array($name, $this->eagerRelations)) {
                                $pdo = $this->pdo;
                                $middlewares = $this->middlewares;
                                $value = $result[$rel['localKey']];
                                $loader = function () use ($pdo, $middlewares, $rel, $value) {
                                        $crud = new Crud($pdo);
                                        foreach ($middlewares as $mw) {
                                                $crud = $crud->withMiddleware($mw);
                                        }
                                        $crud = $crud->from($rel['table'])->where([
                                                $rel['foreignKey'].'__eq' => $value
                                        ]);
                                        $rows = iterator_to_array($crud->select());
                                        if ($rel['type'] === 'hasOne') {
                                                return $rows[0] ?? null;
                                        }
                                        return $rows;
                                };
                                $result[$name] = new LazyRelation($loader);
                        }
                }
                return $result;
        }
	public function next()
	{
		$this->result = $this->stm->fetch();
		$this->i++;
	}
	public function jsonSerialize()
	{
		$this->rewind();
		return $this->stm->fetchAll();
	}
}
