<?php
namespace DBAL\Attributes;
use Attribute;

/**
 * Attribute that declares a property as belonging to a parent record.
 *
 * Used by {@see DBAL\EntityValidationMiddleware} to map a "belongs to" relation
 * for lazy or eager loading.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class BelongsTo {
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
