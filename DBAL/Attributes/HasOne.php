<?php
namespace DBAL\Attributes;
use Attribute;

/**
 * Attribute that specifies a one-to-one relation for an entity property.
 *
 * EntityValidationMiddleware reads this to enable lazy or eager loading.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class HasOne {
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
