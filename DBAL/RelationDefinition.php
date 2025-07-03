<?php
namespace DBAL;

class RelationDefinition
{
    private $name;
    private $table;
    private $type;
    private $conditions = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function hasOne(string $table): self
    {
        $this->type = 'hasOne';
        $this->table = $table;
        return $this;
    }

    public function hasMany(string $table): self
    {
        $this->type = 'hasMany';
        $this->table = $table;
        return $this;
    }

    public function on(string $left, string $operator, string $right): self
    {
        $this->conditions[] = [$left, $operator, $right];
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getConditions(): array
    {
        return $this->conditions;
    }
}
