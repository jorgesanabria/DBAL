<?php
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;

/**
 * Clase/Interfaz LinqMiddleware
 */
class LinqMiddleware implements MiddlewareInterface, CrudAwareMiddlewareInterface
{
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
 * countRows
 * @param Crud $crud
 * @return int
 */

    private function countRows(Crud $crud): int
    {
        $rows = iterator_to_array($crud->select('COUNT(*) AS c'));
        return (int)($rows[0]['c'] ?? 0);
    }

/**
 * any
 * @param Crud $crud
 * @param mixed $...$filters
 * @return bool
 */

    public function any(Crud $crud, ...$filters): bool
    {
        $rows = iterator_to_array($crud->where(...$filters)->limit(1)->select('1'));
        return !empty($rows);
    }

/**
 * none
 * @param Crud $crud
 * @param mixed $...$filters
 * @return bool
 */

    public function none(Crud $crud, ...$filters): bool
    {
        return !$this->any($crud, ...$filters);
    }

/**
 * all
 * @param Crud $crud
 * @param mixed $...$filters
 * @return bool
 */

    public function all(Crud $crud, ...$filters): bool
    {
        $total = $this->countRows($crud);
        if ($total === 0) {
            return true;
        }
        $matched = $this->countRows($crud->where(...$filters));
        return $total === $matched;
    }

/**
 * notAll
 * @param Crud $crud
 * @param mixed $...$filters
 * @return bool
 */

    public function notAll(Crud $crud, ...$filters): bool
    {
        return !$this->all($crud, ...$filters);
    }
}
