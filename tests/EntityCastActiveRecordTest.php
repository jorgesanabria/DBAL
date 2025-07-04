<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use DBAL\Crud;
use DBAL\EntityCastMiddleware;
use DBAL\ActiveRecordTrait;
use DBAL\LazyRelation;
use DBAL\Attributes\HasOne;

class CastUserAr {
    use ActiveRecordTrait;
    public $id;
    public $name;
    #[HasOne('profiles', 'id', 'user_id')]
    public $profile;
}

class EntityCastActiveRecordTest extends TestCase
{
    private function createPdo()
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        $pdo->exec('CREATE TABLE profiles (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, bio TEXT)');
        $pdo->exec("INSERT INTO users (name) VALUES ('Alice')");
        $pdo->exec("INSERT INTO profiles (user_id, bio) VALUES (1, 'bio')");
        return $pdo;
    }

    public function testCastObjectsActAsActiveRecord()
    {
        $pdo = $this->createPdo();
        $mw  = (new EntityCastMiddleware())->register('users', CastUserAr::class);
        $crud = (new Crud($pdo))->from('users');
        $crud = $mw->attach($crud, 'users');

        $rows = iterator_to_array($crud->select());
        $user = $rows[0];
        $this->assertInstanceOf(CastUserAr::class, $user);
        $this->assertInstanceOf(LazyRelation::class, $user->profile);

        $user->name = 'Bob';
        $user->update();

        $rows = iterator_to_array($crud->select());
        $this->assertEquals('Bob', $rows[0]->name);
    }
}
