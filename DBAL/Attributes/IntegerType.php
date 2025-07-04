<?php
declare(strict_types=1);
namespace DBAL\Attributes;
use Attribute;

/**
 * Ensures the decorated property contains an integer value.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class IntegerType {}
