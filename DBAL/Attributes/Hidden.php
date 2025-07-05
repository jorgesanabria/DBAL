<?php
declare(strict_types=1);
namespace DBAL\Attributes;
use Attribute;

/**
 * Marks an entity property as hidden when casting rows.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Hidden {}
