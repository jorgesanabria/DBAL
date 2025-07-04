<?php
declare(strict_types=1);
namespace DBAL;

use PDO;

/**
 * Clase/Interfaz SqlSchemaTableBuilder
 */
class SqlSchemaTableBuilder
{
    private array $definitions = [];

/**
 * __construct
 * @param PDO $pdo
 * @param string $table
 * @param bool $create
 * @return void
 */

    public function __construct(private PDO $pdo, private string $table, private bool $create = true)
    {
    }
/**
 * column
 * @param string $name
 * @param string $type
 * @return self
 */

    public function column(string $name, string $type = null): self
    {
        if ($this->create) {
            if ($type === null) {
                $this->definitions[] = $name;
            } else {
                $this->definitions[] = sprintf('%s %s', $name, $type);
            }
        }
        return $this;
    }

/**
 * addColumn
 * @param string $name
 * @param string $type
 * @return self
 */

    public function addColumn(string $name, string $type = null): self
    {
        if (!$this->create) {
            if ($type === null) {
                $this->definitions[] = sprintf('ADD COLUMN %s', $name);
            } else {
                $this->definitions[] = sprintf('ADD COLUMN %s %s', $name, $type);
            }
        }
        return $this;
    }

/**
 * dropColumn
 * @param string $name
 * @return self
 */

    public function dropColumn(string $name): self
    {
        if (!$this->create) {
            $this->definitions[] = sprintf('DROP COLUMN %s', $name);
        }
        return $this;
    }

/**
 * execute
 * @return void
 */

    public function execute(): void
    {
        if (empty($this->definitions)) {
            return;
        }
        if ($this->create) {
            $node = new \DBAL\Schema\Node\CreateTableNode($this->table, true);
        } else {
            $node = new \DBAL\Schema\Node\AlterTableNode($this->table);
        }
        foreach ($this->definitions as $def) {
            $node->appendChild(new \DBAL\Schema\Node\TableDefinitionNode($def));
        }
        $msg = $node->send(new \DBAL\QueryBuilder\Message());
        $this->pdo->exec($msg->readMessage());
    }
}
