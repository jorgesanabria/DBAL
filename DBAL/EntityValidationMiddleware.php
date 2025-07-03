<?php
namespace DBAL;

use InvalidArgumentException;
use DBAL\QueryBuilder\MessageInterface;
use DBAL\RelationDefinition;

/**
 * Clase/Interfaz EntityValidationMiddleware
 */
class EntityValidationMiddleware implements EntityValidationInterface
{
/** @var mixed */
    private $rules = [];
/** @var mixed */
    private $relations = [];

/** @var mixed */
    private $currentTable;
/** @var mixed */
    private $currentField;

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
        if (!isset($this->rules[$table])) {
            $this->rules[$table] = [];
        }
        if (!isset($this->relations[$table])) {
            $this->relations[$table] = [];
        }
        return $this;
    }

/**
 * field
 * @param string $field
 * @return self
 */

    public function field(string $field): self
    {
        $this->currentField = $field;
        if (!isset($this->rules[$this->currentTable][$field])) {
            $this->rules[$this->currentTable][$field] = [
                'required' => false,
                'validators' => []
            ];
        }
        return $this;
    }

    public function relation(
        string $name,
        string $type = null,
        string $table = null,
        string $localKey = null,
        string $foreignKey = null
    ): RelationDefinition {
        $relation = new RelationDefinition($name);
        $this->relations[$this->currentTable][$name] = $relation;

        if ($type !== null && $table !== null && $localKey !== null && $foreignKey !== null) {
            $relation = match ($type) {
                'hasOne'    => $relation->hasOne($table),
                'hasMany'   => $relation->hasMany($table),
                'belongsTo' => $relation->belongsTo($table),
                default     => throw new InvalidArgumentException('Invalid relation type'),
            };

            $relation->on(
                "{$this->currentTable}.{$localKey}",
                '=',
                "{$table}.{$foreignKey}"
            );
        }

        return $relation;
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

/**
 * required
 * @return self
 */

    public function required(): self
    {
        $this->rules[$this->currentTable][$this->currentField]['required'] = true;
        return $this;
    }

/**
 * string
 * @return self
 */

    public function string(): self
    {
        return $this->addValidator(function ($value) {
            if (!is_string($value)) {
                throw new InvalidArgumentException('Value must be a string');
            }
        });
    }

/**
 * integer
 * @return self
 */

    public function integer(): self
    {
        return $this->addValidator(function ($value) {
            if (!is_int($value)) {
                throw new InvalidArgumentException('Value must be an integer');
            }
        });
    }

/**
 * maxLength
 * @param int $length
 * @return self
 */

    public function maxLength(int $length): self
    {
        return $this->addValidator(function ($value) use ($length) {
            if (is_string($value) && strlen($value) > $length) {
                throw new InvalidArgumentException("Length must be <= {$length}");
            }
        });
    }

/**
 * email
 * @return self
 */

    public function email(): self
    {
        return $this->addValidator(function ($value) {
            if (!is_string($value) || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Invalid email');
            }
        });
    }

/**
 * addValidator
 * @param callable $validator
 * @return self
 */

    private function addValidator(callable $validator): self
    {
        $this->rules[$this->currentTable][$this->currentField]['validators'][] = $validator;
        return $this;
    }

/**
 * beforeInsert
 * @param string $table
 * @param array $fields
 * @return void
 */

    public function beforeInsert(string $table, array $fields): void
    {
        if (!isset($this->rules[$table])) {
            return;
        }
        foreach ($this->rules[$table] as $field => $rule) {
            if (!array_key_exists($field, $fields)) {
                if ($rule['required']) {
                    throw new InvalidArgumentException("Field {$field} is required");
                }
                continue;
            }
            $value = $fields[$field];
            foreach ($rule['validators'] as $validator) {
                $validator($value);
            }
        }
    }

/**
 * beforeUpdate
 * @param string $table
 * @param array $fields
 * @return void
 */

    public function beforeUpdate(string $table, array $fields): void
    {
        if (!isset($this->rules[$table])) {
            return;
        }
        foreach ($fields as $field => $value) {
            if (!isset($this->rules[$table][$field])) {
                continue;
            }
            foreach ($this->rules[$table][$field]['validators'] as $validator) {
                $validator($value);
            }
        }
    }

/**
 * getRelation
 * @param string $table
 * @param string $name
 * @return ?RelationDefinition
 */

    public function getRelation(string $table, string $name): ?RelationDefinition
    {
        return $this->relations[$table][$name] ?? null;
    }
}
