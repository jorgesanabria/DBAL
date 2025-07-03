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

    public function testWhereOrGroup()
    {
        $query = (new Query())->from('users')->where(function ($f) {
            $f->orGroup(function ($g) {
                $g->name__eq('Alice')->orNext()->name__eq('Bob');
            });
        });
        $msg = $query->buildSelect();
        $this->assertEquals('SELECT * FROM users WHERE (name = ? OR name = ?)', $msg->readMessage());
    }

    public function testWherePrecedence()
    {
        $query = (new Query())->from('users')->where(function ($f) {
            $f->orGroup(function ($g) {
                $g->name__eq('Alice')->orNext()->name__eq('Bob');
            })->andGroup(function ($g) {
                $g->status__eq('active');
            });
        });
        $msg = $query->buildSelect();
        $this->assertEquals('SELECT * FROM users WHERE (name = ? OR name = ?) AND status = ?', $msg->readMessage());
    }
}
