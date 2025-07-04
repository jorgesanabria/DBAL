<?php
declare(strict_types=1);
namespace DBAL\Attributes;
use Attribute;

/**
 * Marks an entity class with the table name it represents.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Table {
    public string $name;
    public function __construct(string $name) {
        $this->name = $name;
    }
}
