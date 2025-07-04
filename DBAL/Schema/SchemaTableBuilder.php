<?php
declare(strict_types=1);
namespace DBAL\Schema;

use DBAL\Platform\PlatformInterface;

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

    public function __construct(private string $name, private PlatformInterface $platform)
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
        $builder = new SchemaColumnBuilder($name, $this->platform);
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
        $create = new \DBAL\Schema\Node\CreateTableNode($this->name);
        foreach ($this->columns as $col) {
            $create->appendChild(
                new \DBAL\Schema\Node\TableDefinitionNode($col->build())
            );
        }
        $msg = $create->send(new \DBAL\QueryBuilder\Message());
        return $msg->readMessage();
    }
}
