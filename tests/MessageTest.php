<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use DBAL\QueryBuilder\Message;
use DBAL\QueryBuilder\MessageInterface;

class MessageTest extends TestCase
{
    public function testInsertBeforeAfterReplace()
    {
        $m = new Message(MessageInterface::MESSAGE_TYPE_SELECT, 'FROM foo');
        $m = $m->insertBefore('SELECT *');
        $m = $m->insertAfter('WHERE id = ?');
        $m = $m->addValues([1]);
        $this->assertEquals('SELECT * FROM foo WHERE id = ?', $m->readMessage());
        $this->assertEquals([1], $m->getValues());
        $this->assertSame(1, $m->numValues());
        $this->assertSame(strlen('SELECT * FROM foo WHERE id = ?'), $m->getLength());
    }

    public function testJoinDifferentTypeThrows()
    {
        $this->expectException(InvalidArgumentException::class);
        $m1 = new Message(MessageInterface::MESSAGE_TYPE_SELECT, 'SELECT');
        $m2 = new Message(MessageInterface::MESSAGE_TYPE_INSERT, 'INSERT');
        $m1->join($m2);
    }
}
