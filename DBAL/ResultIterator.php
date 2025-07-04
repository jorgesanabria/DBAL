<?php
declare(strict_types=1);
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;

/**
 * Clase/Interfaz ResultIterator
 */
class ResultIterator implements \Iterator, \JsonSerializable
{
        protected mixed $result;
        protected int $i;
        protected ?\PDOStatement $stm;
        protected array $rows = [];
/**
 * __construct
 * @param \PDO $pdo
 * @param MessageInterface $message
 * @param array $mappers
 * @param array $middlewares
 * @param array $relations
 * @param array $eagerRelations
 * @return void
 */

        public function __construct(
                protected \PDO $pdo,
                protected MessageInterface $message,
                protected array $mappers = [],
                protected array $middlewares = [],
                protected array $relations = [],
                protected array $eagerRelations = []
        ) {
                $this->i = 0;
        }
/**
 * rewind
 * @return mixed
 */

        #[\ReturnTypeWillChange]
        public function rewind(): void
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
/**
 * valid
 * @return mixed
 */

        #[\ReturnTypeWillChange]
        public function valid(): bool
        {
                return $this->i < count($this->rows);
        }
/**
 * key
 * @return mixed
 */

        #[\ReturnTypeWillChange]
        public function key(): mixed
        {
                return $this->i;
        }
/**
 * current
 * @return mixed
 */

        #[\ReturnTypeWillChange]
        public function current(): mixed
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
/**
 * next
 * @return mixed
 */

        #[\ReturnTypeWillChange]
        public function next(): void
        {
                $this->i++;
                $this->result = $this->rows[$this->i] ?? false;
        }
/**
 * jsonSerialize
 * @return mixed
 */

        #[\ReturnTypeWillChange]
        public function jsonSerialize(): mixed
        {
                $this->rewind();
                return $this->rows;
        }

/**
 * groupBy
 * @param mixed $key
 * @return mixed
 */

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
