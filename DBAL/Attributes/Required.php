<?php
declare(strict_types=1);
namespace DBAL\Attributes;
use Attribute;

/**
 * Marks the property as mandatory when validating entities.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Required {}
