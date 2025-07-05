<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use DBAL\Crud;
use DBAL\TypeSecurityMiddleware;
use DBAL\LoggingMiddleware;
use DBAL\Attributes\{Table,StringType,IntegerType,Hidden};

#[Table('users')]
class TsUser {
    #[StringType]
    public $name;
    #[IntegerType]
    public $age;
    #[Hidden]
    public $secret;
}

class TypeSecurityMiddlewareTest extends TestCase
{
    private function createPdo()
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, age INTEGER, secret TEXT)');
        return $pdo;
    }

    public function testCastAndHidden()
    {
        $pdo = $this->createPdo();
        $log = [];
        $logger = function ($sql, $values) use (&$log) { $log[] = [$sql, $values]; };

        $mw = (new TypeSecurityMiddleware())->register(TsUser::class);
        $crud = (new Crud($pdo))->from('users');
        $crud = $mw->attach($crud, 'users')->withMiddleware(new LoggingMiddleware($logger));

        $crud->insert(['name' => 123, 'age' => '20', 'secret' => 'pw']);
        $crud->where(['id' => [\DBAL\QueryBuilder\FilterOp::EQ, 1]])->update(['age' => '21']);
        $row = iterator_to_array($crud->select())[0];

        $this->assertSame(['123', 20, 'pw'], $log[0][1]);
        $this->assertSame([21, 1], $log[1][1]);
        $this->assertArrayNotHasKey('secret', $row);
    }
}
