<?php
use PHPUnit\Framework\TestCase;
use DBAL\Crud;
use DBAL\ActiveRecordMiddleware;
use DBAL\ActiveRecord;
use DBAL\QueryBuilder\MessageInterface;

class ActiveRecordMiddlewareTest extends TestCase
{
    private function createPdo()
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT)');
        $pdo->exec('INSERT INTO users(name,email) VALUES ("Alice","alice@example.com")');
        return $pdo;
    }

    public function testDynamicGettersAndSetters()
    {
        $pdo = $this->createPdo();
        $crud = (new Crud($pdo))->from('users');
        $mw = new ActiveRecordMiddleware();
        $crud = $mw->attach($crud);

        $rows = iterator_to_array($crud->select());
        $record = $rows[0];

        $this->assertInstanceOf(ActiveRecord::class, $record);
        $this->assertEquals('Alice', $record->get__name());
        $record->set__name('Bob');
        $this->assertEquals('Bob', $record->get__name());
    }

    public function testUpdateOnlyChangedFieldsAreSent()
    {
        $pdo = $this->createPdo();
        $log = [];
        $logger = function (MessageInterface $m) use (&$log) {
            if ($m->type() === MessageInterface::MESSAGE_TYPE_UPDATE) {
                $log[] = [$m->readMessage(), $m->getValues()];
            }
        };

        $crud = (new Crud($pdo))->from('users')->withMiddleware($logger);
        $mw = new ActiveRecordMiddleware();
        $crud = $mw->attach($crud);

        $record = iterator_to_array($crud->where(['id__eq' => 1])->select())[0];
        $record->set__name('Alice2');
        $record->set__email('alice@example.com');
        $record->update();

        $this->assertCount(1, $log);
        $this->assertStringContainsString('SET name = ?', $log[0][0]);
        $this->assertStringNotContainsString('email', $log[0][0]);
        $this->assertEquals(['Alice2', 1], $log[0][1]);
    }
}
