<?php
declare(strict_types=1);
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;

/**
 * Clase/Interfaz FirstLastMiddleware
 */
class FirstLastMiddleware implements MiddlewareInterface
{
    private Crud $crud;

/**
 * __invoke
 * @param MessageInterface $msg
 * @return void
 */

    public function __invoke(MessageInterface $msg): void
    {
        // no-op
    }

/**
 * attach
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
 * first
 * @param mixed $...$fields
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
 * firstOrDefault
 * @param mixed $default
 * @param mixed $...$fields
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
 * last
 * @param mixed $...$fields
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
 * lastOrDefault
 * @param mixed $default
 * @param mixed $...$fields
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
