<?php
declare(strict_types=1);
namespace DBAL;

use ReflectionClass;
use DBAL\QueryBuilder\MessageInterface;
use DBAL\RelationDefinition;
use DBAL\Attributes\HasOne;
use DBAL\Attributes\HasMany;
use DBAL\Attributes\BelongsTo;

/**
 * Middleware that casts result rows into objects of a given class and
 * infers relations from its attributes for eager loading.
 */
class EntityCastMiddleware implements MiddlewareInterface
{
    private array $classes = [];
    private array $relations = [];

    public function __invoke(MessageInterface $msg): void
    {
        // no-op
    }

    /**
     * Register an entity class for a table. Attribute based relations are
     * parsed and stored for later joins.
     */
    public function register(string $table, string $class): self
    {
        $this->classes[$table] = $class;
        $this->relations[$table] = [];

        $ref = new ReflectionClass($class);
        foreach ($ref->getProperties() as $prop) {
            foreach ($prop->getAttributes() as $attr) {
                $name = $attr->getName();
                if (in_array($name, [HasOne::class, HasMany::class, BelongsTo::class], true)) {
                    $def  = new RelationDefinition($prop->getName());
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
                    $this->relations[$table][$prop->getName()] = $def;
                }
            }
        }

        return $this;
    }

    /**
     * Returns relation definitions for a given table.
     */
    public function getRelations(string $table): array
    {
        return $this->relations[$table] ?? [];
    }

    /**
     * Attach the middleware to a Crud instance and cast rows for the table.
     */
    public function attach(Crud $crud, string $table): Crud
    {
        if (!isset($this->classes[$table])) {
            return $crud->withMiddleware($this);
        }
        $class = $this->classes[$table];
        $crud  = $crud->map(function (array $row) use ($class) {
            $obj = new $class();
            foreach ($row as $k => $v) {
                $obj->$k = $v;
            }
            return $obj;
        });
        return $crud->withMiddleware($this);
    }
}
