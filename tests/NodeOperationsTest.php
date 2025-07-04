<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use DBAL\QueryBuilder\Node\Node;
use DBAL\QueryBuilder\Node\EmptyNode;
use DBAL\QueryBuilder\Message;
use DBAL\QueryBuilder\MessageInterface;

class DummyNode extends Node
{
    protected bool $isEmpty = false;
    public function send(MessageInterface $message)
    {
        return $message->insertAfter('dummy');
    }
}

class NodeOperationsTest extends TestCase
{
    private function createNode()
    {
        return new class extends Node {
            protected bool $isEmpty = false;
            public function send(MessageInterface $message) { return $message; }
        };
    }

    public function testChildManagement(): void
    {
        $parent = $this->createNode();
        $child  = new DummyNode();
        $key = $parent->appendChild($child);
        $this->assertEquals(0, $key);
        $this->assertTrue($parent->hasChild($key));
        $this->assertSame($child, $parent->getChild($key));

        $removed = $parent->removeChild($key);
        $this->assertSame($child, $removed);
        $this->assertFalse($parent->hasChild($key));
        $this->assertInstanceOf(EmptyNode::class, $parent->getChild('missing'));
        $this->assertInstanceOf(EmptyNode::class, $parent->removeChild('missing'));
    }

    public function testCloneDeepCopiesChildren(): void
    {
        $parent = $this->createNode();
        $child  = new DummyNode();
        $parent->appendChild($child);
        $clone = clone $parent;
        $this->assertNotSame($parent->getChild(0), $clone->getChild(0));
    }
}
