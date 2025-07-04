<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use DBAL\QueryBuilder\Message;
use DBAL\QueryBuilder\MessageInterface;
use DBAL\QueryBuilder\Node\FilterNode;

class FilterNodeTest extends TestCase
{
    public function testFilterEq()
    {
        $node = new FilterNode(['name__eq' => 'Alice']);
        $msg = $node->send(new Message());
        $this->assertEquals('name = ?', $msg->readMessage());
        $this->assertEquals(['Alice'], $msg->getValues());
    }

    public function testInWithSubquery()
    {
        $sub = new Message(MessageInterface::MESSAGE_TYPE_SELECT);
        $sub = $sub->insertAfter('SELECT id FROM users');
        $node = new FilterNode(['id__in' => $sub]);
        $msg = $node->send(new Message());
        $this->assertEquals('id in (SELECT id FROM users)', $msg->readMessage());
    }
}
