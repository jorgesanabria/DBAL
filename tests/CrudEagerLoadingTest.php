<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use DBAL\Crud;
use DBAL\EntityValidationMiddleware;
use DBAL\Attributes\HasOne;

class UserWithProfile {
    #[HasOne('profiles', 'id', 'user_id')]
    public $profile;
}

class CrudEagerLoadingTest extends TestCase
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

    public function testEagerLoadJoin()
    {
        $pdo = $this->createPdo();
        $mw = (new EntityValidationMiddleware())
            ->register('users', UserWithProfile::class);

        $crud = (new Crud($pdo))->from('users')->withMiddleware($mw)->with('profile');
        $rows = iterator_to_array($crud->select('users.id', 'profiles.bio'));
        $this->assertCount(1, $rows);
        $this->assertEquals('bio', $rows[0]['bio']);
    }
}
