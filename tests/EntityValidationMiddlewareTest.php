<?php
use PHPUnit\Framework\TestCase;
use DBAL\Crud;
use DBAL\EntityValidationMiddleware;
use DBAL\EntityValidationInterface;

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
            ->table('users')
                ->field('name')->required()->string()->maxLength(50)
                ->field('email')->required()->email();
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
        $crud->where(['id__eq' => $id])->update(['email' => 'not-an-email']);
    }

    public function testUpdateValidData()
    {
        $pdo = $this->createPdo();
        $crud = $this->createCrud($pdo);
        $id = $crud->insert(['name' => 'Carol', 'email' => 'carol@example.com']);
        $count = $crud->where(['id__eq' => $id])->update(['name' => 'Caro']);
        $this->assertEquals(1, $count);
    }

    public function testGetRelations()
    {
        $mw = (new EntityValidationMiddleware())
            ->table('users')
                ->relation('profile', 'hasOne', 'profiles', 'id', 'user_id');

        $expected = [
            'profile' => [
                'type' => 'hasOne',
                'table' => 'profiles',
                'localKey' => 'id',
                'foreignKey' => 'user_id',
            ],
        ];

        $this->assertEquals($expected, $mw->getRelations('users'));
    }
}
