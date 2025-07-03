<?php
use PHPUnit\Framework\TestCase;
use DBAL\EntityValidationMiddleware;
use DBAL\RelationDefinition;

class RelationDefinitionTest extends TestCase
{
    public function testRelationBuilderStoresCondition()
    {
        $mw = (new EntityValidationMiddleware())
            ->table('users')
                ->relation('profile')
                    ->hasOne('profiles')
                    ->on('users.id', '=', 'profiles.user_id');

        $rel = $mw->getRelation('users', 'profile');
        $this->assertInstanceOf(RelationDefinition::class, $rel);
        $this->assertEquals('profiles', $rel->getTable());
        $this->assertEquals([[ 'users.id', '=', 'profiles.user_id' ]], $rel->getConditions());
    }
}
