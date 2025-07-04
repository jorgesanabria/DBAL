<?php
declare(strict_types=1);
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
        $query = (new Query())->from('users')->where(['id' => [\DBAL\QueryBuilder\FilterOp::EQ, 1]]);
        $msg = $query->buildSelect();
        $this->assertEquals('SELECT * FROM users WHERE id = ?', $msg->readMessage());
        $this->assertEquals([1], $msg->getValues());
    }

    public function testWhereOrGroup()
    {
        $query = (new Query())->from('users')->where(function ($f) {
            $f->orGroup(function ($g) {
                $g->condition('name', \DBAL\QueryBuilder\FilterOp::EQ, 'Alice')->orNext()->condition('name', \DBAL\QueryBuilder\FilterOp::EQ, 'Bob');
            });
        });
        $msg = $query->buildSelect();
        $this->assertEquals('SELECT * FROM users WHERE (name = ? OR name = ?)', $msg->readMessage());
    }

    public function testWherePrecedence()
    {
        $query = (new Query())->from('users')->where(function ($f) {
            $f->orGroup(function ($g) {
                $g->condition('name', \DBAL\QueryBuilder\FilterOp::EQ, 'Alice')->orNext()->condition('name', \DBAL\QueryBuilder\FilterOp::EQ, 'Bob');
            })->andGroup(function ($g) {
                $g->condition('status', \DBAL\QueryBuilder\FilterOp::EQ, 'active');
            });
        });
        $msg = $query->buildSelect();
        $this->assertEquals('SELECT * FROM users WHERE (name = ? OR name = ?) AND status = ?', $msg->readMessage());
    }

    public function testJoinWithDynamicFilter()
    {
        $query = (new Query())
            ->from('users u')
            ->leftJoin('profiles p', function ($j) {
                $j->condition('u.id', \DBAL\QueryBuilder\FilterOp::EQF, 'p.user_id');
            });
        $msg = $query->buildSelect();
        $this->assertEquals('SELECT * FROM users u LEFT JOIN profiles p ON u.id = p.user_id', $msg->readMessage());
    }

    public function testGroupByAlias()
    {
        $groupSql = (new Query())
            ->from('users')
            ->group('status')
            ->buildSelect()
            ->readMessage();

        $groupBySql = (new Query())
            ->from('users')
            ->groupBy('status')
            ->buildSelect()
            ->readMessage();

        $this->assertEquals($groupSql, $groupBySql);
    }
}
