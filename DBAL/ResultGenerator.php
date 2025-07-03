<?php
namespace DBAL;

use Generator;
use PDO;
use DBAL\QueryBuilder\MessageInterface;

/**
 * Clase/Interfaz ResultGenerator
 */
class ResultGenerator
{
/** @var mixed */
    private $pdo;
/** @var mixed */
    private $message;
/** @var mixed */
    private $mappers;
/** @var mixed */
    private $middlewares;
/** @var mixed */
    private $relations;
/** @var mixed */
    private $eagerRelations;

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

    public function __construct(PDO $pdo, MessageInterface $message, array $mappers = [], array $middlewares = [], array $relations = [], array $eagerRelations = [])
    {
        $this->pdo = $pdo;
        $this->message = $message;
        $this->mappers = $mappers;
        $this->middlewares = $middlewares;
        $this->relations = $relations;
        $this->eagerRelations = $eagerRelations;
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
            if (!in_array($name, $this->eagerRelations)) {
                $pdo = $this->pdo;
                $middlewares = $this->middlewares;
                if (!array_key_exists($rel['localKey'], $row)) {
                    throw new \RuntimeException(sprintf(
                        'Missing local key %s for relation %s',
                        $rel['localKey'],
                        $name
                    ));
                }
                $value = $row[$rel['localKey']];
                $loader = function () use ($pdo, $middlewares, $rel, $value) {
                    $crud = new Crud($pdo);
                    foreach ($middlewares as $mw) {
                        $crud = $crud->withMiddleware($mw);
                    }
                    $crud = $crud->from($rel['table'])->where([
                        $rel['foreignKey'] . '__eq' => $value,
                    ]);
                    $rows = iterator_to_array($crud->select());
                    if (in_array($rel['type'], ['hasOne', 'belongsTo'])) {
                        return $rows[0] ?? null;
                    }
                    return $rows;
                };
                $row[$name] = new LazyRelation($loader);
            }
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

        foreach ($this->middlewares as $mw) {
            if (method_exists($mw, 'save')) {
                $mw->save($this->message, $rows);
            }
        }
    }
}
