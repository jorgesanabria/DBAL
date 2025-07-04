<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use DBAL\QueryBuilder\CustomFilterBuilder;

class CustomFilterBuilderTest extends TestCase
{
    public function testCustomMethod()
    {
        $builder = new CustomFilterBuilder();
        $builder->isWoman()->age__gt(18);
        $this->assertEquals([
            'gender__eq' => 'fem',
            'age__gt'    => 18,
        ], $builder->toArray());
    }
}
