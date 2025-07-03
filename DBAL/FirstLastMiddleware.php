<?php
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;

class FirstLastMiddleware implements MiddlewareInterface
{
    private $crud;

    public function __invoke(MessageInterface $msg): void
    {
        // no-op
    }

    public function attach(Crud $crud): Crud
    {
        $crud = $crud->withMiddleware($this);
        $this->crud = $crud;
        return $crud;
    }

    public function first(...$fields)
    {
        $rows = iterator_to_array($this->crud->limit(1)->select(...$fields));
        if (!isset($rows[0])) {
            throw new \RuntimeException('No record found');
        }
        return $rows[0];
    }

    public function firstOrDefault($default = null, ...$fields)
    {
        $rows = iterator_to_array($this->crud->limit(1)->select(...$fields));
        if (!isset($rows[0])) {
            return is_callable($default) ? $default() : $default;
        }
        return $rows[0];
    }

    public function last(...$fields)
    {
        $rows = iterator_to_array($this->crud->select(...$fields));
        if (empty($rows)) {
            throw new \RuntimeException('No record found');
        }
        return $rows[count($rows) - 1];
    }

    public function lastOrDefault($default = null, ...$fields)
    {
        $rows = iterator_to_array($this->crud->select(...$fields));
        if (empty($rows)) {
            return is_callable($default) ? $default() : $default;
        }
        return $rows[count($rows) - 1];
    }
}
