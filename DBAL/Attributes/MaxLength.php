<?php
namespace DBAL\Attributes;
use Attribute;
#[Attribute(Attribute::TARGET_PROPERTY)]
class MaxLength {
    public int $length;
    public function __construct(int $length) { $this->length = $length; }
}
