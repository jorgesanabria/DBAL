<?php
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;

class LinqMiddleware implements MiddlewareInterface, CrudAwareMiddlewareInterface
{
    public function __invoke(MessageInterface $msg): void
    {
        // no-op
    }

    private function countRows(Crud $crud): int
    {
        $rows = iterator_to_array($crud->select('COUNT(*) AS c'));
        return (int)($rows[0]['c'] ?? 0);
    }

    public function any(Crud $crud, ...$filters): bool
    {
        $rows = iterator_to_array($crud->where(...$filters)->limit(1)->select('1'));
        return !empty($rows);
    }

    public function none(Crud $crud, ...$filters): bool
    {
        return !$this->any($crud, ...$filters);
    }

    public function all(Crud $crud, ...$filters): bool
    {
        $total = $this->countRows($crud);
        if ($total === 0) {
            return true;
        }
        $matched = $this->countRows($crud->where(...$filters));
        return $total === $matched;
    }

    public function notAll(Crud $crud, ...$filters): bool
    {
        return !$this->all($crud, ...$filters);
    }
}
