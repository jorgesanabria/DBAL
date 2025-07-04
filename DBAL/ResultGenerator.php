<?php
declare(strict_types=1);
namespace DBAL;

use Generator;
use PDO;
use DBAL\QueryBuilder\MessageInterface;
use DBAL\AfterExecuteMiddlewareInterface;
use DBAL\RelationDefinition;

/**
 * Clase/Interfaz ResultGenerator
 */
class ResultGenerator
{

/**
 * __construct
 * @param PDO $pdo
 * @param MessageInterface $message
 * @param array $mappers
 * @param array $middlewares
 * @param array $relations
 * @param array $eagerRelations
 * @return void
 */

    public function __construct(
        private PDO $pdo,
        private MessageInterface $message,
        private array $mappers = [],
        private array $middlewares = [],
        private array $relations = [],
        private array $eagerRelations = []
    ) {
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
 * applyMappers
 * @param mixed $row
 * @return mixed
 */

    private function applyMappers($row)
    {
        foreach ($this->mappers as $mapper) {
            $row = $mapper($row);
        }
        return $row;
    }

/**
 * applyLazyRelations
 * @param mixed $row
 * @return mixed
 */

    private function applyLazyRelations($row)
    {
        foreach ($this->relations as $name => $rel) {
            if (in_array($name, $this->eagerRelations)) {
                continue;
            }

            $info = $this->relationInfo($rel);
            if ($info === null) {
                continue;
            }
            [$localKey, $foreignKey, $table, $type] = $info;

            if (!array_key_exists($localKey, $row)) {
                throw new \RuntimeException(sprintf(
                    'Missing local key %s for relation %s',
                    $localKey,
                    $name
                ));
            }
            $value = $row[$localKey];
            $loader = $this->relationLoader($table, $foreignKey, $type, $value);
            $row[$name] = new LazyRelation($loader);
        }

        return $row;
    }

/**
 * getIterator
 * @param callable $callback
 * @return Generator
 */

    public function getIterator(callable $callback = null): Generator
    {
        foreach ($this->middlewares as $mw) {
            $mw($this->message);
        }

        foreach ($this->middlewares as $mw) {
            if (method_exists($mw, 'fetch')) {
                $cached = $mw->fetch($this->message);
                if ($cached !== null) {
                    foreach ($cached as $row) {
                        $row = $this->applyMappers($row);
                        $row = $this->applyLazyRelations($row);
                        if ($callback) {
                            $callback($row);
                        }
                        yield $row;
                    }
                    return;
                }
            }
        }

        $stm = $this->pdo->prepare($this->message->readMessage());
        $start = microtime(true);
        $stm->execute($this->message->getValues());

        $rows = [];
        while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
            $row = $this->applyMappers($row);
            $row = $this->applyLazyRelations($row);
            if ($callback) {
                $callback($row);
            }
            yield $row;
        }

        $time = microtime(true) - $start;

        foreach ($this->middlewares as $mw) {
            if (method_exists($mw, 'save')) {
                $mw->save($this->message, $rows);
            }
        }

        foreach ($this->middlewares as $mw) {
            if ($mw instanceof AfterExecuteMiddlewareInterface) {
                $mw->afterExecute($this->message, $time);
            }
        }
    }
}
