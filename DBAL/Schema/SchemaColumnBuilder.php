<?php
declare(strict_types=1);
namespace DBAL\Schema;

use DBAL\Platform\PlatformInterface;

/**
 * Helper for assembling column definitions in CREATE/ALTER statements.
 */
class SchemaColumnBuilder
{
    private string $type = '';
    private array $constraints = [];

/**
 * __construct
 * @param string $name
 * @return void
 */

    public function __construct(private string $name, private PlatformInterface $platform)
    {    }

/**
 * type
 * @param string $type
 * @return self
 */

    public function type(string $type): self
    {
        $this->type = strtoupper($type);
        return $this;
    }

/**
 * integer
 * @return self
 */

    public function integer(): self
    {
        return $this->type('INTEGER');
    }

/**
 * text
 * @return self
 */

    public function text(): self
    {
        return $this->type('TEXT');
    }

/**
 * real
 * @return self
 */

    public function real(): self
    {
        return $this->type('REAL');
    }

/**
 * primaryKey
 * @return self
 */

    public function primaryKey(): self
    {
        $this->constraints[] = 'PRIMARY KEY';
        return $this;
    }

/**
 * autoIncrement
 * @return self
 */

    public function autoIncrement(): self
    {
        $this->constraints[] = $this->platform->autoIncrementKeyword();
        return $this;
    }

/**
 * build
 * @return string
 */

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
