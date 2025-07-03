<?php
use PHPUnit\Framework\TestCase;
use DBAL\Crud;
use DBAL\EntityValidationMiddleware;

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
            ->table('users')
                ->relation('profile')
                    ->hasOne('profiles')
                    ->on('users.id', '=', 'profiles.user_id');

        $crud = (new Crud($pdo))->from('users')->withMiddleware($mw)->with('profile');
        $rows = iterator_to_array($crud->select('users.id', 'profiles.bio'));
        $this->assertCount(1, $rows);
        $this->assertEquals('bio', $rows[0]['bio']);
    }
}
