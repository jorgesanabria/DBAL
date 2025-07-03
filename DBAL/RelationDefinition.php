<?php
namespace DBAL;

/**
 * Clase/Interfaz RelationDefinition
 */
class RelationDefinition
{
/** @var mixed */
    private $name;
/** @var mixed */
    private $table;
/** @var mixed */
    private $type;
/** @var mixed */
    private $conditions = [];

/**
 * __construct
 * @param string $name
 * @return void
 */

    public function __construct(string $name)
    {
        $this->name = $name;
    }

/**
 * hasOne
 * @param string $table
 * @return self
 */

    public function hasOne(string $table): self
    {
        $this->type = 'hasOne';
        $this->table = $table;
        return $this;
    }

/**
 * hasMany
 * @param string $table
 * @return self
 */

    public function hasMany(string $table): self
    {
        $this->type = 'hasMany';
        $this->table = $table;
        return $this;
    }

/**
 * belongsTo
 * @param string $table
 * @return self
 */

    public function belongsTo(string $table): self
    {
        $this->type = 'belongsTo';
        $this->table = $table;
        return $this;
    }

/**
 * on
 * @param string $left
 * @param string $operator
 * @param string $right
 * @return self
 */

    public function on(string $left, string $operator, string $right): self
    {
        $this->conditions[] = [$left, $operator, $right];
        return $this;
    }

/**
 * getName
 * @return string
 */

    public function getName(): string
    {
        return $this->name;
    }

/**
 * getTable
 * @return string
 */

    public function getTable(): string
    {
        return $this->table;
    }

/**
 * getType
 * @return string
 */

    public function getType(): string
    {
        return $this->type;
    }

/**
 * getConditions
 * @return array
 */

    public function getConditions(): array
    {
        return $this->conditions;
    }
}
