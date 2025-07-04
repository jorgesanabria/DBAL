<?php
declare(strict_types=1);
namespace DBAL\Attributes;
use Attribute;

/**
 * Validates that the property value is a string.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class StringType {}
