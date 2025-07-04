<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use DBAL\Crud;
use DBAL\EntityCastMiddleware;
use DBAL\LazyRelation;
use DBAL\Attributes\HasOne;
use DBAL\QueryBuilder\MessageInterface;

class CastUser {
    public $id;
    public $name;
    #[HasOne('profiles', 'id', 'user_id')]
    public $profile;
}

class EntityCastMiddlewareTest extends TestCase
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

    public function testRowsAreCastedToObjects()
    {
        $pdo = $this->createPdo();
        $mw  = (new EntityCastMiddleware())->register('users', CastUser::class);
        $crud = (new Crud($pdo))->from('users');
        $crud = $mw->attach($crud, 'users');

        $rows = iterator_to_array($crud->select());
        $this->assertInstanceOf(CastUser::class, $rows[0]);
        $this->assertInstanceOf(LazyRelation::class, $rows[0]->profile);
    }

    public function testEagerLoadAddsJoin()
    {
        $pdo = $this->createPdo();
        $log = [];
        $logger = function (MessageInterface $m) use (&$log) { $log[] = $m->readMessage(); };

        $mw  = (new EntityCastMiddleware())->register('users', CastUser::class);
        $crud = (new Crud($pdo))->from('users');
        $crud = $mw->attach($crud, 'users')->withMiddleware($logger)->with('profile');

        iterator_to_array($crud->select('users.id', 'profiles.bio'));
        $this->assertStringContainsString('JOIN profiles', $log[0]);
    }
}
