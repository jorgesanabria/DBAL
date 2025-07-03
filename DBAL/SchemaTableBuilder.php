<?php
namespace DBAL;

use PDO;

class SchemaTableBuilder
{
    private $pdo;
    private $table;
    private $create = true;
    private $definitions = [];

    public function __construct(PDO $pdo, string $table, bool $create = true)
    {
        $this->pdo = $pdo;
        $this->table = $table;
        $this->create = $create;
    }

    public function column(string $name, string $type): self
    {
        if ($this->create) {
            $this->definitions[] = sprintf('%s %s', $name, $type);
        }
        return $this;
    }

    public function addColumn(string $name, string $type): self
    {
        if (!$this->create) {
            $this->definitions[] = sprintf('ADD COLUMN %s %s', $name, $type);
        }
        return $this;
    }

    public function dropColumn(string $name): self
    {
        if (!$this->create) {
            $this->definitions[] = sprintf('DROP COLUMN %s', $name);
        }
        return $this;
    }

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
