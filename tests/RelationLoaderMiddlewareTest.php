<?php
use PHPUnit\Framework\TestCase;
use DBAL\Crud;
use DBAL\RelationLoaderMiddleware;
use DBAL\QueryBuilder\MessageInterface;

class RelationLoaderMiddlewareTest extends TestCase
{
    private function createPdo()
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        $pdo->exec('CREATE TABLE profiles (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, bio TEXT)');
        $pdo->exec('INSERT INTO users(name) VALUES ("Alice")');
        $pdo->exec('INSERT INTO profiles(user_id, bio) VALUES (1, "Bio")');
        return $pdo;
    }

    public function testEagerLoadingAddsJoin()
    {
        $pdo = $this->createPdo();
        $log = [];
        $logger = function (MessageInterface $m) use (&$log) { $log[] = $m->readMessage(); };

        $rel = (new RelationLoaderMiddleware())
            ->table('users')
            ->hasOne('profile', 'profiles', 'id', 'user_id');

        $crud = (new Crud($pdo))
            ->from('users')
            ->withMiddleware($rel)
            ->withMiddleware($logger);

        iterator_to_array($crud->with('profile')->select());

        $this->assertStringContainsString('LEFT JOIN profiles', $log[0]);
    }

    public function testLazyLoadingFetchesOnDemand()
    {
        $pdo = $this->createPdo();
        $log = [];
        $logger = function (MessageInterface $m) use (&$log) { $log[] = $m->readMessage(); };

        $rel = (new RelationLoaderMiddleware())
            ->table('users')
            ->hasOne('profile', 'profiles', 'id', 'user_id');

        $crud = (new Crud($pdo))
            ->from('users')
            ->withMiddleware($rel)
            ->withMiddleware($logger);

        $rows = iterator_to_array($crud->select());
        $this->assertStringNotContainsString('profiles', $log[0]);

        $profile = $rows[0]['profile']->get();
        $this->assertEquals('Bio', $profile['bio']);
        $this->assertStringContainsString('FROM profiles', $log[1]);
    }
}
