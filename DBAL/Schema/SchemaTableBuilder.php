<?php
declare(strict_types=1);
namespace DBAL\Schema;

/**
 * Clase/Interfaz SchemaTableBuilder
 */
class SchemaTableBuilder
{
    /** @var SchemaColumnBuilder[] */
    private array $columns = [];

/**
 * __construct
 * @param string $name
 * @return void
 */

    public function __construct(private string $name)
    {    }

/**
 * column
 * @param string $name
 * @param mixed $typeOrCallback
 * @return self
 */

    public function column(string $name, $typeOrCallback): self
    {
        return $this->addColumn($name, $typeOrCallback);
    }

/**
 * addColumn
 * @param string $name
 * @param mixed $typeOrCallback
 * @return self
 */

    public function addColumn(string $name, $typeOrCallback): self
    {
        $builder = new SchemaColumnBuilder($name);
        if (is_callable($typeOrCallback)) {
            $typeOrCallback($builder);
        } else {
            $builder->type($typeOrCallback);
        }
        $this->columns[] = $builder;
        return $this;
    }

/**
 * build
 * @return string
 */

    public function build(): string
    {
        $cols = array_map(function (SchemaColumnBuilder $col) {
            return $col->build();
        }, $this->columns);
        return sprintf('CREATE TABLE %s (%s)', $this->name, implode(', ', $cols));
    }
}
