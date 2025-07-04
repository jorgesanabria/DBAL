<?php
namespace DBAL\Attributes;
use Attribute;
#[Attribute(Attribute::TARGET_PROPERTY)]
class HasOne {
    public string $table;
    public string $localKey;
    public string $foreignKey;
    public function __construct(string $table, string $localKey, string $foreignKey) {
        $this->table = $table;
        $this->localKey = $localKey;
        $this->foreignKey = $foreignKey;
    }
}
