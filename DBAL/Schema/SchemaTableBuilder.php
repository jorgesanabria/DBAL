<?php
namespace DBAL\Schema;

class SchemaTableBuilder
{
    private $name;
    /** @var SchemaColumnBuilder[] */
    private $columns = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function column(string $name, $typeOrCallback): self
    {
        return $this->addColumn($name, $typeOrCallback);
    }

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

    public function build(): string
    {
        $cols = array_map(function (SchemaColumnBuilder $col) {
            return $col->build();
        }, $this->columns);
        return sprintf('CREATE TABLE %s (%s)', $this->name, implode(', ', $cols));
    }
}
