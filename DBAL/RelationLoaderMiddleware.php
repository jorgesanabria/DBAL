<?php
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;

class RelationLoaderMiddleware implements MiddlewareInterface
{
    private $currentTable;
    private $relations = [];

    public function __invoke(MessageInterface $msg): void
    {
        // no-op
    }

    public function table(string $table): self
    {
        $this->currentTable = $table;
        if (!isset($this->relations[$table])) {
            $this->relations[$table] = [];
        }
        return $this;
    }

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

    public function getRelations(string $table): array
    {
        return $this->relations[$table] ?? [];
    }
}
