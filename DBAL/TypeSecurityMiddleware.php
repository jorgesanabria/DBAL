<?php
declare(strict_types=1);
namespace DBAL;

use ReflectionClass;
use DBAL\Attributes\{IntegerType,StringType,Hidden,Table};
use DBAL\QueryBuilder\MessageInterface;

/**
 * Middleware that casts incoming data based on entity attributes
 * and hides marked fields from fetched rows.
 */
class TypeSecurityMiddleware implements EntityCastInterface
{
    private array $casts = [];
    private array $hidden = [];

    public function __invoke(MessageInterface $msg): void
    {
        // no-op
    }

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
                throw new \InvalidArgumentException('Table name missing and no #[Table] attribute found');
            }
            $table = $attrs[0]->newInstance()->name;
        }

        $this->casts[$table] = [];
        $this->hidden[$table] = [];

        foreach ($ref->getProperties() as $prop) {
            $field = $prop->getName();
            $cast = null;
            if ($prop->getAttributes(StringType::class)) {
                $cast = 'string';
            }
            if ($prop->getAttributes(IntegerType::class)) {
                $cast = 'int';
            }
            if ($cast !== null) {
                $this->casts[$table][$field] = $cast;
            }
            if ($prop->getAttributes(Hidden::class)) {
                $this->hidden[$table][] = $field;
            }
        }

        return $this;
    }

    public function castInsert(string $table, array $fields): array
    {
        if (!isset($this->casts[$table])) {
            return $fields;
        }
        foreach ($fields as $k => $v) {
            if (isset($this->casts[$table][$k])) {
                settype($v, $this->casts[$table][$k]);
                $fields[$k] = $v;
            }
        }
        return $fields;
    }

    public function castUpdate(string $table, array $fields): array
    {
        return $this->castInsert($table, $fields);
    }

    public function attach(Crud $crud, string $table): Crud
    {
        if (isset($this->hidden[$table])) {
            $hidden = $this->hidden[$table];
            $crud = $crud->map(function (array $row) use ($hidden) {
                foreach ($hidden as $f) {
                    unset($row[$f]);
                }
                return $row;
            });
        }
        return $crud->withMiddleware($this);
    }
}
