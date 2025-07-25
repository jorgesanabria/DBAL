<?php
declare(strict_types=1);
namespace DBAL;

use InvalidArgumentException;
use ReflectionClass;
use DBAL\QueryBuilder\MessageInterface;
use DBAL\RelationDefinition;
use DBAL\Attributes\Required;
use DBAL\Attributes\StringType;
use DBAL\Attributes\IntegerType;
use DBAL\Attributes\MaxLength;
use DBAL\Attributes\Email;
use DBAL\Attributes\Hidden;
use DBAL\Attributes\HasOne;
use DBAL\Attributes\HasMany;
use DBAL\Attributes\BelongsTo;
use DBAL\Attributes\Table;

/**
 * EntityValidationMiddleware parses attribute annotations on entity classes
 * to configure validation rules and relations.
 */
class EntityValidationMiddleware implements EntityValidationInterface
{
    private array $rules = [];
    private array $relations = [];
    private array $casts = [];
    private array $hidden = [];

    public function __invoke(MessageInterface $msg): void
    {
        // no-op
    }

    /**
     * Register an entity class for a given table.
     */
    public function register(string $table, string $class = null): self
    {
        if ($class === null && class_exists($table)) {
            $class = $table;
            $table = null;
        }

        $ref = new ReflectionClass($class);

        if ($table === null) {
            $attrs = $ref->getAttributes(Table::class);
            if (!$attrs) {
                throw new InvalidArgumentException('Table name missing and no #[Table] attribute found');
            }
            $table = $attrs[0]->newInstance()->name;
        }

        $this->rules[$table] = [];
        $this->relations[$table] = [];
        $this->casts[$table] = [];
        $this->hidden[$table] = [];

        foreach ($ref->getProperties() as $prop) {
            $field = $prop->getName();
            $rule = [
                'required' => false,
                'validators' => []
            ];
            $cast = null;

            if ($prop->getAttributes(Required::class)) {
                $rule['required'] = true;
            }
            foreach ($prop->getAttributes(StringType::class) as $a) {
                $rule['validators'][] = function ($value) {
                    if (!is_string($value)) {
                        throw new InvalidArgumentException('Value must be a string');
                    }
                };
                $cast = 'string';
            }
            foreach ($prop->getAttributes(IntegerType::class) as $a) {
                $rule['validators'][] = function ($value) {
                    if (!is_int($value)) {
                        throw new InvalidArgumentException('Value must be an integer');
                    }
                };
                $cast = 'int';
            }
            foreach ($prop->getAttributes(MaxLength::class) as $a) {
                $len = $a->newInstance()->length;
                $rule['validators'][] = function ($value) use ($len) {
                    if (is_string($value) && strlen($value) > $len) {
                        throw new InvalidArgumentException("Length must be <= {$len}");
                    }
                };
            }
            foreach ($prop->getAttributes(Email::class) as $a) {
                $rule['validators'][] = function ($value) {
                    if (!is_string($value) || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        throw new InvalidArgumentException('Invalid email');
                    }
                };
            }

            if ($cast !== null) {
                $this->casts[$table][$field] = $cast;
            }
            if ($prop->getAttributes(Hidden::class)) {
                $this->hidden[$table][] = $field;
            }

            if ($rule['required'] || $rule['validators']) {
                $this->rules[$table][$field] = $rule;
            }

            foreach ($prop->getAttributes() as $attr) {
                $name = $attr->getName();
                if (in_array($name, [HasOne::class, HasMany::class, BelongsTo::class], true)) {
                    $def = new RelationDefinition($field);
                    $inst = $attr->newInstance();
                    switch ($name) {
                        case HasOne::class:
                            $def->hasOne($inst->table);
                            break;
                        case HasMany::class:
                            $def->hasMany($inst->table);
                            break;
                        case BelongsTo::class:
                            $def->belongsTo($inst->table);
                            break;
                    }
                    $def->on("{$table}.{$inst->localKey}", '=', "{$inst->table}.{$inst->foreignKey}");
                    $this->relations[$table][$field] = $def;
                }
            }
        }

        return $this;
    }

    public function getRelations(string $table): array
    {
        return $this->relations[$table] ?? [];
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
