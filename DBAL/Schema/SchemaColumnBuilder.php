<?php
namespace DBAL\Schema;

class SchemaColumnBuilder
{
    private $name;
    private $type = '';
    private $constraints = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function type(string $type): self
    {
        $this->type = strtoupper($type);
        return $this;
    }

    public function integer(): self
    {
        return $this->type('INTEGER');
    }

    public function text(): self
    {
        return $this->type('TEXT');
    }

    public function real(): self
    {
        return $this->type('REAL');
    }

    public function primaryKey(): self
    {
        $this->constraints[] = 'PRIMARY KEY';
        return $this;
    }

    public function autoIncrement(): self
    {
        $this->constraints[] = 'AUTOINCREMENT';
        return $this;
    }

    public function build(): string
    {
        $parts = [$this->name];
        if ($this->type) {
            $parts[] = $this->type;
        }
        if ($this->constraints) {
            $parts[] = implode(' ', $this->constraints);
        }
        return implode(' ', $parts);
    }
}
