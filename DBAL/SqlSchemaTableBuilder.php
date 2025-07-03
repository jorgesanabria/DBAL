<?php
namespace DBAL;

use PDO;

/**
 * Clase/Interfaz SqlSchemaTableBuilder
 */
class SqlSchemaTableBuilder
{
/** @var mixed */
    private $pdo;
/** @var mixed */
    private $table;
/** @var mixed */
    private $create = true;
/** @var mixed */
    private $definitions = [];

/**
 * __construct
 * @param PDO $pdo
 * @param string $table
 * @param bool $create
 * @return void
 */

    public function __construct(PDO $pdo, string $table, bool $create = true)
    {
        $this->pdo = $pdo;
        $this->table = $table;
        $this->create = $create;
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
            $sql = sprintf(
                'CREATE TABLE IF NOT EXISTS %s (%s)',
                $this->table,
                implode(', ', $this->definitions)
            );
        } else {
            $sql = sprintf(
                'ALTER TABLE %s %s',
                $this->table,
                implode(', ', $this->definitions)
            );
        }
        $this->pdo->exec($sql);
    }
}
