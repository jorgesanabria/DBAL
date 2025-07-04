<?php
declare(strict_types=1);
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;

/**
 * Middleware that provides LINQ-style helper methods for querying.
 *
 * When attached to a {@see Crud} instance it exposes `any()`, `none()`, `all()`,
 * `notAll()`, `count()`, `max()`, `min()` and `sum()` methods.
 */
class LinqMiddleware implements MiddlewareInterface, CrudAwareMiddlewareInterface
{
    /**
     * Part of the middleware chain; it performs no action.
     */
    public function __invoke(MessageInterface $msg): void
    {
        // no-op
    }

    /**
     * Helper used by other methods to count rows.
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

/**
 * count
 * @param Crud $crud
 * @param mixed $...$filters
 * @return int
 */

    public function count(Crud $crud, ...$filters): int
    {
        return $this->countRows($crud->where(...$filters));
    }

    /**
     * Ensure that the given identifier is safe to use in a query.
     */
    private function quoteIdentifier(string $id): string
    {
        $parts = explode('.', $id);
        foreach ($parts as $i => $p) {
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $p)) {
                throw new \InvalidArgumentException("Invalid identifier: {$id}");
            }
            $parts[$i] = '"' . $p . '"';
        }
        return implode('.', $parts);
    }

/**
 * max
 * @param Crud $crud
 * @param string $field
 * @return mixed
 */

    public function max(Crud $crud, string $field)
    {
        $field = $this->quoteIdentifier($field);
        $rows = iterator_to_array($crud->select("MAX($field) AS m"));
        return $rows[0]['m'] ?? null;
    }

/**
 * min
 * @param Crud $crud
 * @param string $field
 * @return mixed
 */

    public function min(Crud $crud, string $field)
    {
        $field = $this->quoteIdentifier($field);
        $rows = iterator_to_array($crud->select("MIN($field) AS m"));
        return $rows[0]['m'] ?? null;
    }

/**
 * sum
 * @param Crud $crud
 * @param string $field
 * @return float
 */

    public function sum(Crud $crud, string $field): float
    {
        $field = $this->quoteIdentifier($field);
        $rows = iterator_to_array($crud->select("SUM($field) AS s"));
        return (float)($rows[0]['s'] ?? 0);
    }

    /**
     * average
     * @param Crud $crud
     * @param string $field
     * @return float
     */
    public function average(Crud $crud, string $field): float
    {
        $field = $this->quoteIdentifier($field);
        $rows = iterator_to_array($crud->select("AVG($field) AS a"));
        return (float)($rows[0]['a'] ?? 0);
    }

    /**
     * distinct
     * @param Crud $crud
     * @param string $field
     * @return array
     */
    public function distinct(Crud $crud, string $field): array
    {
        $field = $this->quoteIdentifier($field);
        $rows = iterator_to_array($crud->select("DISTINCT $field AS d"));
        return array_map(fn($r) => $r['d'], $rows);
    }
}
