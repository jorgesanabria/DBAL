<?php
declare(strict_types=1);
namespace DBAL\Attributes;
use Attribute;

/**
 * Attribute declaring a one-to-many relation for a property.
 *
 * The relation is consumed by {@see DBAL\EntityValidationMiddleware}.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class HasMany {
    public string $table;
    public string $localKey;
    public string $foreignKey;

    /**
     * @param string $table      Related table name
     * @param string $localKey   Key on the current table
     * @param string $foreignKey Key on the related table
     */
    public function __construct(string $table, string $localKey, string $foreignKey) {
        $this->table = $table;
        $this->localKey = $localKey;
        $this->foreignKey = $foreignKey;
    }
}
