<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use DBAL\QueryBuilder\Message;
use DBAL\QueryBuilder\Node\LimitNode;
use DBAL\Platform\SqlitePlatform;

class LimitNodeTest extends TestCase
{
    public function testLimitAndOffset()
    {
        $node = new LimitNode(new SqlitePlatform());
        $node->setLimit(10);
        $node->setOffset(5);
        $msg = $node->send(new Message());
        $this->assertEquals('LIMIT ? OFFSET ?', $msg->readMessage());
        $this->assertEquals([10,5], $msg->getValues());
    }

    public function testOnlyLimit()
    {
        $node = new LimitNode(new SqlitePlatform());
        $node->setLimit(3);
        $msg = $node->send(new Message());
        $this->assertEquals('LIMIT ?', $msg->readMessage());
        $this->assertEquals([3], $msg->getValues());
    }
}
