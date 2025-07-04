<?php
declare(strict_types=1);
namespace DBAL\Attributes;
use Attribute;

/**
 * Restricts a string property to a maximum length.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class MaxLength {
    public int $length;

    /**
     * @param int $length Allowed maximum number of characters
     */
    public function __construct(int $length) { $this->length = $length; }
}
