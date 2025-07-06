<?php
declare(strict_types=1);
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;

/**
 * Middleware used to register relations for eager or lazy loading.
 */
class RelationLoaderMiddleware implements MiddlewareInterface
{
    private string $currentTable;
    private array $relations = [];

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
 * table
 * @param string $table
 * @return self
 */

    public function table(string $table): self
    {
        $this->currentTable = $table;
        if (!isset($this->relations[$table])) {
            $this->relations[$table] = [];
        }
        return $this;
    }

/**
 * hasOne
 * @param string $name
 * @param string $table
 * @param string $localKey
 * @param string $foreignKey
 * @param callable $on
 * @param string $joinType
 * @return self
 */

    public function hasOne(string $name, string $table, string $localKey, string $foreignKey, callable $on = null, string $joinType = 'left'): self
    {
        if ($on === null) {
            $local = $this->currentTable;
            $on = function ($j) use ($local, $table, $localKey, $foreignKey) {
                $j->{"{$local}.{$localKey}__eqf"}("{$table}.{$foreignKey}");
            };
        }
        $this->relations[$this->currentTable][$name] = [
            'type' => 'hasOne',
            'table' => $table,
            'localKey' => $localKey,
            'foreignKey' => $foreignKey,
            'joinType' => $joinType,
            'on' => $on,
        ];
        return $this;
    }

/**
 * hasMany
 * @param string $name
 * @param string $table
 * @param string $localKey
 * @param string $foreignKey
 * @param callable $on
 * @param string $joinType
 * @return self
 */

    public function hasMany(string $name, string $table, string $localKey, string $foreignKey, callable $on = null, string $joinType = 'left'): self
    {
        if ($on === null) {
            $local = $this->currentTable;
            $on = function ($j) use ($local, $table, $localKey, $foreignKey) {
                $j->{"{$local}.{$localKey}__eqf"}("{$table}.{$foreignKey}");
            };
        }
        $this->relations[$this->currentTable][$name] = [
            'type' => 'hasMany',
            'table' => $table,
            'localKey' => $localKey,
            'foreignKey' => $foreignKey,
            'joinType' => $joinType,
            'on' => $on,
        ];
        return $this;
    }

/**
 * belongsTo
 * @param string $name
 * @param string $table
 * @param string $localKey
 * @param string $foreignKey
 * @param callable $on
 * @param string $joinType
 * @return self
 */

    public function belongsTo(string $name, string $table, string $localKey, string $foreignKey, callable $on = null, string $joinType = 'left'): self
    {
        if ($on === null) {
            $local = $this->currentTable;
            $on = function ($j) use ($local, $table, $localKey, $foreignKey) {
                $j->{"{$local}.{$localKey}__eqf"}("{$table}.{$foreignKey}");
            };
        }
        $this->relations[$this->currentTable][$name] = [
            'type' => 'belongsTo',
            'table' => $table,
            'localKey' => $localKey,
            'foreignKey' => $foreignKey,
            'joinType' => $joinType,
            'on' => $on,
        ];
        return $this;
    }

/**
 * getRelations
 * @param string $table
 * @return array
 */

    public function getRelations(string $table): array
    {
        return $this->relations[$table] ?? [];
    }
}
