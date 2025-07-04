<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use DBAL\Crud;
use DBAL\EntityValidationMiddleware;
use DBAL\EntityValidationInterface;
use DBAL\RelationDefinition;
use DBAL\Attributes\{Required, StringType, MaxLength, Email, HasOne, BelongsTo, Table};

#[Table('users')]
class UserEntity {
    #[Required]
    #[StringType]
    #[MaxLength(50)]
    public $name;

    #[Required]
    #[Email]
    public $email;

    #[HasOne('profiles', 'id', 'user_id')]
    public $profile;
}

#[Table('profiles')]
class ProfileEntity {
    #[BelongsTo('users', 'user_id', 'id')]
    public $user;
}

class EntityValidationMiddlewareTest extends TestCase
{
    private function createPdo()
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT)');
        return $pdo;
    }

    private function createCrud(PDO $pdo)
    {
        $mw = (new EntityValidationMiddleware())
            ->register(UserEntity::class);
        return (new Crud($pdo))->from('users')->withMiddleware($mw);
    }

    public function testInsertInvalidDataThrows()
    {
        $crud = $this->createCrud($this->createPdo());
        $this->expectException(InvalidArgumentException::class);
        $crud->insert(['email' => 'foo@example.com']);
    }

    public function testInsertValidData()
    {
        $pdo = $this->createPdo();
        $crud = $this->createCrud($pdo);
        $id = $crud->insert(['name' => 'Alice', 'email' => 'alice@example.com']);
        $this->assertEquals(1, $id);
    }

    public function testUpdateInvalidDataThrows()
    {
        $pdo = $this->createPdo();
        $crud = $this->createCrud($pdo);
        $id = $crud->insert(['name' => 'Bob', 'email' => 'bob@example.com']);
        $this->expectException(InvalidArgumentException::class);
        $crud->where(['id' => [\DBAL\QueryBuilder\FilterOp::EQ, $id]])->update(['email' => 'not-an-email']);
    }

    public function testUpdateValidData()
    {
        $pdo = $this->createPdo();
        $crud = $this->createCrud($pdo);
        $id = $crud->insert(['name' => 'Carol', 'email' => 'carol@example.com']);
        $count = $crud->where(['id' => [\DBAL\QueryBuilder\FilterOp::EQ, $id]])->update(['name' => 'Caro']);
        $this->assertEquals(1, $count);
    }

    public function testGetRelations()
    {
        $mw = (new EntityValidationMiddleware())
            ->register(UserEntity::class);

        $rels = $mw->getRelations('users');
        $this->assertArrayHasKey('profile', $rels);
        $this->assertInstanceOf(DBAL\RelationDefinition::class, $rels['profile']);
    }

    public function testRelationShortcut()
    {
        $mw = (new EntityValidationMiddleware())
            ->register(UserEntity::class);

        $rel = $mw->getRelation('users', 'profile');
        $this->assertInstanceOf(RelationDefinition::class, $rel);
        $this->assertEquals('hasOne', $rel->getType());
        $this->assertEquals('profiles', $rel->getTable());
        $this->assertEquals([
            ['users.id', '=', 'profiles.user_id']
        ], $rel->getConditions());
    }
}
