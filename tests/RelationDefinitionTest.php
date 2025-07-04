<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use DBAL\EntityValidationMiddleware;
use DBAL\RelationDefinition;
use DBAL\Attributes\{HasOne, Table};

#[Table('users')]
class UserRelationEntity {
    #[HasOne('profiles', 'id', 'user_id')]
    public $profile;
}

class RelationDefinitionTest extends TestCase
{
    public function testRelationBuilderStoresCondition()
    {
        $mw = (new EntityValidationMiddleware())
            ->register(UserRelationEntity::class);

        $rel = $mw->getRelation('users', 'profile');
        $this->assertInstanceOf(RelationDefinition::class, $rel);
        $this->assertEquals('profiles', $rel->getTable());
        $this->assertEquals([[ 'users.id', '=', 'profiles.user_id' ]], $rel->getConditions());
    }
}
