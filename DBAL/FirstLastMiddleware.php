<?php
declare(strict_types=1);
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;

/**
 * Middleware providing helpers to retrieve the first or last record of a query.
 */
class FirstLastMiddleware implements MiddlewareInterface
{
    private Crud $crud;

/**
 * Execute the middleware. This implementation performs no action.
 *
 * @param MessageInterface $msg
 */
    public function __invoke(MessageInterface $msg): void
    {
        // no-op
    }

/**
 * Register the middleware on the given Crud instance and return it.
 *
 * @param Crud $crud
 * @return Crud
 */

    public function attach(Crud $crud): Crud
    {
        $crud = $crud->withMiddleware($this);
        $this->crud = $crud;
        return $crud;
    }

/**
 * Return the first row produced by the current query.
 *
 * @param mixed ...$fields
 * @return mixed
 */

    public function first(...$fields)
    {
        $rows = iterator_to_array($this->crud->limit(1)->select(...$fields));
        if (!isset($rows[0])) {
            throw new \RuntimeException('No record found');
        }
        return $rows[0];
    }

/**
 * Return the first row or the provided default when no rows exist.
 *
 * @param mixed $default
 * @param mixed ...$fields
 * @return mixed
 */

    public function firstOrDefault($default = null, ...$fields)
    {
        $rows = iterator_to_array($this->crud->limit(1)->select(...$fields));
        if (!isset($rows[0])) {
            return is_callable($default) ? $default() : $default;
        }
        return $rows[0];
    }

/**
 * Return the last row from the result set.
 *
 * @param mixed ...$fields
 * @return mixed
 */

    public function last(...$fields)
    {
        $rows = iterator_to_array($this->crud->select(...$fields));
        if (empty($rows)) {
            throw new \RuntimeException('No record found');
        }
        return $rows[count($rows) - 1];
    }

/**
 * Return the last row or the provided default when the query yields no rows.
 *
 * @param mixed $default
 * @param mixed ...$fields
 * @return mixed
 */

    public function lastOrDefault($default = null, ...$fields)
    {
        $rows = iterator_to_array($this->crud->select(...$fields));
        if (empty($rows)) {
            return is_callable($default) ? $default() : $default;
        }
        return $rows[count($rows) - 1];
    }
}
