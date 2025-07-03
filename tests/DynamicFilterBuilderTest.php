<?php
use PHPUnit\Framework\TestCase;
use DBAL\QueryBuilder\DynamicFilterBuilder;

class DynamicFilterBuilderTest extends TestCase
{
    public function testMagicCallAndToArray()
    {
        $builder = new DynamicFilterBuilder();
        $builder->name__eq('Alice')->age__ge(21);
        $this->assertEquals([
            'name__eq' => 'Alice',
            'age__ge' => 21,
        ], $builder->toArray());
    }
}
