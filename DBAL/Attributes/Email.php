<?php
namespace DBAL\Attributes;
use Attribute;

/**
 * Marks a property as requiring a valid email address during validation.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Email {}
