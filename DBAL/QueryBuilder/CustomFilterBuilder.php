<?php
declare(strict_types=1);

namespace DBAL\QueryBuilder;

/**
 * Example extension of DynamicFilterBuilder with domain specific helpers.
 */
class CustomFilterBuilder extends DynamicFilterBuilder
{
    /**
     * Adds the condition gender__eq('fem').
     */
    public function isWoman(): self
    {
        parent::__call('gender__eq', ['fem']);
        return $this;
    }
}
