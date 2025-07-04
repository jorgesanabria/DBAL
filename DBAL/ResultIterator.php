<?php
declare(strict_types=1);
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;
use DBAL\AfterExecuteMiddlewareInterface;
use DBAL\RelationDefinition;

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
         * Extract relation information from the given definition.
         */
        private function relationInfo(mixed $rel): ?array
        {
                if ($rel instanceof RelationDefinition) {
                        $cond = $rel->getConditions()[0] ?? null;
                        if (!$cond || $cond[1] !== '=') {
                                return null;
                        }
                        $localKey = explode('.', $cond[0])[1] ?? $cond[0];
                        $foreignKey = explode('.', $cond[2])[1] ?? $cond[2];
                        $table = $rel->getTable();
                        $type = $rel->getType();
                } else {
                        $localKey = $rel['localKey'];
                        $foreignKey = $rel['foreignKey'];
                        $table = $rel['table'];
                        $type = $rel['type'];
                }

                return [$localKey, $foreignKey, $table, $type];
        }

        /**
         * Retrieve the value of the local key from the current result.
         */
        private function localValue(mixed $result, string $localKey, string $name)
        {
                if (is_array($result)) {
                        if (!array_key_exists($localKey, $result)) {
                                throw new \RuntimeException(sprintf(
                                        'Missing local key %s for relation %s',
                                        $localKey,
                                        $name
                                ));
                        }
                        return $result[$localKey];
                }

                if (!isset($result->$localKey)) {
                        throw new \RuntimeException(sprintf(
                                'Missing local key %s for relation %s',
                                $localKey,
                                $name
                        ));
                }

                return $result->$localKey;
        }

        /**
         * Assign a LazyRelation instance to the given result.
         */
        private function assignRelation(mixed &$result, string $name, callable $loader): void
        {
                if (is_array($result)) {
                        $result[$name] = new LazyRelation($loader);
                } else {
                        $result->$name = new LazyRelation($loader);
                }
        }

        /**
         * Create a loader closure for the relation.
         */
        private function relationLoader(string $table, string $foreignKey, string $type, mixed $value): callable
        {
                $pdo = $this->pdo;
                $middlewares = $this->middlewares;

                return function () use ($pdo, $middlewares, $table, $foreignKey, $type, $value) {
                        $crud = new Crud($pdo);
                        foreach ($middlewares as $mw) {
                                $crud = $crud->withMiddleware($mw);
                        }
                        $crud = $crud->from($table)->where([
                                $foreignKey . '__eq' => $value,
                        ]);
                        $rows = iterator_to_array($crud->select());
                        if (in_array($type, ['hasOne', 'belongsTo'])) {
                                return $rows[0] ?? null;
                        }
                        return $rows;
                };
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
                $start = microtime(true);
                $this->stm->execute($this->message->getValues());
                $time = microtime(true) - $start;
                $this->rows = $this->stm->fetchAll(\PDO::FETCH_ASSOC);

                foreach ($this->middlewares as $mw) {
                        if (method_exists($mw, 'save')) {
                                $mw->save($this->message, $this->rows);
                        }
                }

                foreach ($this->middlewares as $mw) {
                        if ($mw instanceof AfterExecuteMiddlewareInterface) {
                                $mw->afterExecute($this->message, $time);
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
                foreach ($this->mappers as $mapper) {
                        $result = call_user_func_array($mapper, [$result]);
                }

                foreach ($this->relations as $name => $rel) {
                        if (in_array($name, $this->eagerRelations)) {
                                continue;
                        }

                        $info = $this->relationInfo($rel);
                        if ($info === null) {
                                continue;
                        }
                        [$localKey, $foreignKey, $table, $type] = $info;

                        $value = $this->localValue($result, $localKey, $name);
                        $loader = $this->relationLoader($table, $foreignKey, $type, $value);
                        $this->assignRelation($result, $name, $loader);
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
