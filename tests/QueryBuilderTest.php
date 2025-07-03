<?php
use PHPUnit\Framework\TestCase;
use DBAL\QueryBuilder\Query;
use DBAL\QueryBuilder\Node\FieldNode;

class QueryBuilderTest extends TestCase
{
    public function testBuildSelectWithFields()
    {
        $query = (new Query())->from('users');
        $msg = $query->buildSelect('id', new FieldNode('name'));
        $this->assertEquals('SELECT id, name FROM users', $msg->readMessage());
    }

    public function testWhereFilter()
    {
        $query = (new Query())->from('users')->where(['id__eq' => 1]);
        $msg = $query->buildSelect();
        $this->assertEquals('SELECT * FROM users WHERE id = ?', $msg->readMessage());
        $this->assertEquals([1], $msg->getValues());
    }
}
