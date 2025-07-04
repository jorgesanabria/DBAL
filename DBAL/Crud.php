<?php
declare(strict_types=1);
namespace DBAL;

use DBAL\QueryBuilder\Query;
use DBAL\QueryBuilder\MessageInterface;
use DBAL\RelationDefinition;
use DBAL\AbmEventInterface;
use Generator;

/**
 * Clase/Interfaz Crud
 */
class Crud extends Query
{
        protected array $mappers = [];
        protected array $middlewares = [];
        protected array $tables = [];
        protected array $with = [];
    /**
     * Creates a new CRUD helper bound to a PDO connection.
     *
     * The connection is stored for later use by the query builder
     * and will be used by every CRUD operation executed through this
     * instance.
     *
     * @param \PDO $connection Database connection used for all queries.
     */

        public function __construct(protected \PDO $connection)
        {
                parent::__construct();
        }
    /**
     * Registers a mapper callback to transform each fetched row.
     *
     * The mapper will be executed when results are iterated in
     * {@see select()} or {@see stream()}.
     *
     * @param callable $callback Callback that receives a row array and
     *                           returns the transformed value.
     * @return self  New instance containing the mapper.
     */

        public function map(callable $callback)
        {
                $clon = clone $this;
                $clon->mappers[] = $callback;
                return $clon;
        }
    /**
     * Adds a middleware that will intercept query execution.
     *
     * Middlewares receive the {@link MessageInterface} before the SQL
     * statement is executed and may alter it or perform additional work.
     *
     * @param callable $mw Middleware callable or object.
     * @return self       New instance that contains the middleware.
     */

        public function withMiddleware(callable $mw)
        {
                $clon = clone $this;
                $clon->middlewares[] = $mw;
                return $clon;
        }

    /**
     * Defines the table or tables from which records will be selected.
     *
     * The method clones the current query object, appends the table names
     * to the underlying query builder and tracks the first table as the
     * primary table for insert/update/delete operations.
     *
     * @param string ...$tables List of tables or table nodes.
     * @return self             New instance configured with the tables.
     */

        public function from(...$tables)
        {
                $clon = parent::from(...$tables);
                foreach ($tables as $table) {
                        $clon->tables[] = $table;
                }
                return $clon;
        }

    /**
     * Eagerly loads relations defined by middlewares.
     *
     * For each requested relation the corresponding JOIN clause is
     * added to the query using the definitions provided by registered
     * middlewares. The method returns a new instance with the relation
     * names stored so that lazy loading can be performed later.
     *
     * @param string ...$relations Names of relations to join.
     * @return self                New instance prepared with the joins.
     */

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

/**
 * primaryTable
 * @return mixed
 */

        private function primaryTable()
        {
                return $this->tables[0] ?? '';
        }
/**
 * runMiddlewares
 * @param MessageInterface $message
 * @return mixed
 */

        protected function runMiddlewares(MessageInterface $message)
        {
                foreach ($this->middlewares as $mw)
                        $mw($message);
        }
/**
 * collectRelations
 * @param mixed $table
 * @return mixed
 */

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
    /**
     * Executes a SELECT query and returns a lazy iterator.
     *
     * The returned {@link ResultIterator} will run registered middlewares
     * when iteration starts and apply any mapper callbacks to each row.
     *
     * @param string|array ...$fields Fields to select. If empty all fields
     *                                from the primary table are returned.
     * @return ResultIterator Iterator over the query results.
     */

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

    /**
     * Returns all rows from a SELECT query as an array.
     *
     * This is a convenience method equivalent to
     * `iterator_to_array($this->select(...$fields))`.
     *
     * @param string|array ...$fields Optional fields to select.
     * @return array                   Array with the fetched rows.
     */

        public function fetchAll(...$fields): array
        {
                return iterator_to_array($this->select(...$fields));
        }

    /**
     * Streams the result of a SELECT query using a generator.
     *
     * Middlewares are executed when the returned generator starts
     * yielding values. Optionally a callback can be provided to handle
     * each row as soon as it is fetched.
     *
     * @param callable|string ...$args Either a callback followed by fields
     *                                 or only the list of fields to select.
     * @return Generator Generator yielding mapped rows.
     */

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
    /**
     * Inserts a single row into the primary table.
     *
     * Validation middlewares implementing
     * {@link EntityValidationInterface} are executed before the
     * statement is built. All registered middlewares then receive the
     * generated message prior to execution. After the insert the
     * {@link AbmEventInterface} hooks are triggered.
     *
     * @param array $fields Associative array of column values to insert.
     * @return mixed        The value returned by PDO::lastInsertId().
     */

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
                $id = $this->connection->lastInsertId();
                foreach ($this->middlewares as $mw) {
                        if ($mw instanceof AbmEventInterface) {
                                $mw->afterInsert($this->primaryTable(), $fields, $id);
                        }
                }
                return $id;
        }
    /**
     * Inserts multiple rows in a single statement.
     *
     * Each row is validated using any registered
     * {@link EntityValidationInterface} middlewares. After the SQL is
     * generated all middlewares are executed once and finally the
     * {@link AbmEventInterface} bulk insert hook is triggered.
     *
     * @param array $rows Array of associative arrays representing rows.
     * @return int        Number of inserted rows reported by PDO.
     */

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
                $count = $stm->rowCount();
                foreach ($this->middlewares as $mw) {
                        if ($mw instanceof AbmEventInterface) {
                                $mw->afterBulkInsert($this->primaryTable(), $rows, $count);
                        }
                }
                return $count;
        }
    /**
     * Updates records in the primary table using the current filters.
     *
     * Validation middlewares are invoked prior to building the SQL.
     * After the UPDATE statement is created, all middlewares receive the
     * message and may modify it. After execution the appropriate
     * {@link AbmEventInterface} hook is called.
     *
     * @param array $fields Column values to update.
     * @return int          Number of affected rows.
     */

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
                $count = $stm->rowCount();
                foreach ($this->middlewares as $mw) {
                        if ($mw instanceof AbmEventInterface) {
                                $mw->afterUpdate($this->primaryTable(), $fields, $count);
                        }
                }
                return $count;
        }
    /**
     * Deletes records matching the current filters.
     *
     * All registered middlewares receive the DELETE message prior to
     * execution. After the statement is executed the delete event from
     * {@link AbmEventInterface} is triggered.
     *
     * @return int Number of affected rows.
     */

       public function delete()
       {
               $message = $this->buildDelete();
               $this->runMiddlewares($message);
               $stm = $this->connection->prepare($message->readMessage());
               $stm->execute($message->getValues());
               $count = $stm->rowCount();
               foreach ($this->middlewares as $mw) {
                       if ($mw instanceof AbmEventInterface) {
                               $mw->afterDelete($this->primaryTable(), $count);
                       }
               }
               return $count;
       }

    /**
     * Forwards unknown method calls to registered middlewares.
     *
     * If a middleware object exposes a method with the given name, it is
     * invoked. When the middleware implements
     * {@link CrudAwareMiddlewareInterface} the current Crud instance is
     * prepended to the arguments.
     *
     * @param string $name      Method name being called.
     * @param array  $arguments Arguments passed to the method.
     * @return mixed            Result returned by the middleware method.
     *
     * @throws \BadMethodCallException When no middleware implements the
     *                                  requested method.
     */

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
