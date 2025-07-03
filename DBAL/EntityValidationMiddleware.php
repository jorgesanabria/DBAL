<?php
namespace DBAL;

use InvalidArgumentException;
use DBAL\QueryBuilder\MessageInterface;
use DBAL\RelationDefinition;

class EntityValidationMiddleware implements EntityValidationInterface
{
    private $rules = [];
    private $relations = [];

    private $currentTable;
    private $currentField;

    public function __invoke(MessageInterface $msg): void
    {
        // no-op
    }

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

    public function relation(string $name): RelationDefinition
    {
        $relation = new RelationDefinition($name);
        $this->relations[$this->currentTable][$name] = $relation;
        return $relation;
    }

    public function getRelations(string $table): array
    {
        return $this->relations[$table] ?? [];
    }

    public function required(): self
    {
        $this->rules[$this->currentTable][$this->currentField]['required'] = true;
        return $this;
    }

    public function string(): self
    {
        return $this->addValidator(function ($value) {
            if (!is_string($value)) {
                throw new InvalidArgumentException('Value must be a string');
            }
        });
    }

    public function integer(): self
    {
        return $this->addValidator(function ($value) {
            if (!is_int($value)) {
                throw new InvalidArgumentException('Value must be an integer');
            }
        });
    }

    public function maxLength(int $length): self
    {
        return $this->addValidator(function ($value) use ($length) {
            if (is_string($value) && strlen($value) > $length) {
                throw new InvalidArgumentException("Length must be <= {$length}");
            }
        });
    }

    public function email(): self
    {
        return $this->addValidator(function ($value) {
            if (!is_string($value) || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Invalid email');
            }
        });
    }

    private function addValidator(callable $validator): self
    {
        $this->rules[$this->currentTable][$this->currentField]['validators'][] = $validator;
        return $this;
    }

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

    public function getRelation(string $table, string $name): ?RelationDefinition
    {
        return $this->relations[$table][$name] ?? null;
    }
}
