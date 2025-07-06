<?php
declare(strict_types=1);
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;
use DBAL\QueryBuilder\Message;

/**
 * Applies predefined filters to SELECT statements automatically.
 */
class GlobalFilterMiddleware implements MiddlewareInterface
{
    private array $tableFilters = [];
    private array $globalFilters = [];

/**
 * Create the middleware with optional table specific and global filters.
 *
 * @param array $tableFilters  Filters applied per table
 * @param array $globalFilters Filters applied to all tables
 */

    public function __construct(array $tableFilters = [], array $globalFilters = [])
    {
        foreach ($tableFilters as $table => $filters) {
            $this->tableFilters[$table] = is_array($filters) ? $filters : [$filters];
        }
        foreach ($globalFilters as $f) {
            $this->globalFilters[] = $f;
        }
    }

/**
 * addFilter
 * @param string|null $table
 * @param callable $filter
 * @return self
 */

    public function addFilter($table, callable $filter): self
    {
        if ($table === null) {
            $this->globalFilters[] = $filter;
        } else {
            if (!isset($this->tableFilters[$table])) {
                $this->tableFilters[$table] = [];
            }
            $this->tableFilters[$table][] = $filter;
        }
        return $this;
    }

/**
 * Apply the configured filters to the given query message.
 *
 * @param MessageInterface $msg
 */

    public function __invoke(MessageInterface $msg): void
    {
        if ($msg->type() !== MessageInterface::MESSAGE_TYPE_SELECT) {
            return;
        }

        $tables = $this->extractTables($msg->readMessage());
        $filters = $this->globalFilters;
        foreach ($tables as $t) {
            if (isset($this->tableFilters[$t])) {
                $filters = array_merge($filters, $this->tableFilters[$t]);
            }
        }

        foreach ($filters as $f) {
            $new = $f($msg);
            if ($new instanceof MessageInterface) {
                $this->apply($msg, $new);
            }
        }
    }

/**
 * apply
 * @param MessageInterface $dest
 * @param MessageInterface $src
 * @return void
 */

    private function apply(MessageInterface $dest, MessageInterface $src): void
    {
        if (!($dest instanceof Message) || !($src instanceof Message)) {
            return;
        }
        $ref = new \ReflectionObject($dest);
        $prop = $ref->getProperty('message');
        $prop->setAccessible(true);
        $prop->setValue($dest, $src->readMessage());
        $prop = $ref->getProperty('values');
        $prop->setAccessible(true);
        $prop->setValue($dest, $src->getValues());
    }

/**
 * extractTables
 * @param string $sql
 * @return array
 */

    private function extractTables(string $sql): array
    {
        $tables = [];
        if (preg_match_all('/\b(?:FROM|JOIN|UPDATE|INTO)\s+([`"\w\\.]+)(?:\s|$)/i', $sql, $m)) {
            foreach ($m[1] as $t) {
                $parts = preg_split('/\s+/', $t);
                $tables[] = trim($parts[0], '`"');
            }
        }
        return array_unique($tables);
    }
}
