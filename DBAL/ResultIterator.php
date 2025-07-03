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
        protected $rows = [];
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

                foreach ($this->middlewares as $mw) {
                        if (method_exists($mw, 'fetch')) {
                                $cached = $mw->fetch($this->message);
                                if ($cached !== null) {
                                        $this->rows = $cached;
                                        $this->i = 0;
                                        $this->result = $this->rows[0] ?? false;
                                        return;
                                }
                        }
                }

                $this->stm = $this->pdo->prepare($this->message->readMessage());
                $this->stm->execute($this->message->getValues());
                $this->rows = $this->stm->fetchAll();

                foreach ($this->middlewares as $mw) {
                        if (method_exists($mw, 'save')) {
                                $mw->save($this->message, $this->rows);
                        }
                }

                $this->i = 0;
                $this->result = $this->rows[0] ?? false;
        }
        public function valid()
        {
                return $this->i < count($this->rows);
        }
        public function key()
        {
                return $this->i;
        }
        public function current()
        {
                $result = $this->rows[$this->i];
                foreach ($this->mappers as $mapper)
                        $result = call_user_func_array($mapper, [$result]);

                foreach ($this->relations as $name => $rel) {
                        if (!in_array($name, $this->eagerRelations)) {
                                $pdo = $this->pdo;
                                $middlewares = $this->middlewares;
                                if (!array_key_exists($rel['localKey'], $result)) {
                                        throw new \RuntimeException(sprintf(
                                                'Missing local key %s for relation %s',
                                                $rel['localKey'],
                                                $name
                                        ));
                                }
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
                                        if (in_array($rel['type'], ['hasOne', 'belongsTo'])) {
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
                $this->i++;
                $this->result = $this->rows[$this->i] ?? false;
        }
        public function jsonSerialize()
        {
                $this->rewind();
                return $this->rows;
        }

        public function groupBy($key)
        {
                $groups = [];
                $this->rewind();
                foreach ($this as $row) {
                        $groupKey = is_callable($key)
                                ? $key($row)
                                : ($row[$key] ?? null);
                        $groups[$groupKey][] = $row;
                }
                return $groups;
        }
}
