<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use DBAL\Crud;
use DBAL\QueryBuilder\MessageInterface;

class CrudMiddlewareMagicCallTest extends TestCase
{
    private function createPdo()
    {
        return new PDO('sqlite::memory:');
    }

    public function testMiddlewareMethodsAreProxied()
    {
        $pdo = $this->createPdo();
        $pdo->exec('CREATE TABLE test (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');

        $mw = new class {
            public function __invoke(MessageInterface $msg): void {}
            public function hello($name)
            {
                return "Hello $name";
            }
        };

        $crud = (new Crud($pdo))->from('test')->withMiddleware($mw);

        $this->assertEquals('Hello Bob', $crud->hello('Bob'));
    }
}
