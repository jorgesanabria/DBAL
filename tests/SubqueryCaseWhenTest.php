<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use DBAL\QueryBuilder\Query;
use DBAL\QueryBuilder\Node\CaseNode;

class SubqueryCaseWhenTest extends TestCase
{
    public function testSubqueryInWhere()
    {
        $sub = (new Query())
            ->from('users')
            ->where(['status' => 'active'])
            ->subQuery('id');

        $msg = (new Query())
            ->from('posts')
            ->where(['user_id__in' => $sub])
            ->buildSelect();

        $this->assertEquals(
            'SELECT * FROM posts WHERE user_id in (SELECT id FROM users WHERE status = ?)',
            $msg->readMessage()
        );
        $this->assertEquals(['active'], $msg->getValues());
    }

    public function testCaseWhenExpression()
    {
        $case = (new CaseNode())
            ->when('status = 1', "'active'")
            ->when('status = 0', "'inactive'")
            ->else("'unknown'")
            ->as('state');

        $msg = (new Query())
            ->from('users')
            ->buildSelect($case);

        $this->assertEquals(
            "SELECT CASE WHEN status = 1 THEN 'active' WHEN status = 0 THEN 'inactive' ELSE 'unknown' END AS state FROM users",
            $msg->readMessage()
        );
    }

    public function testSubqueryAsField()
    {
        $subField = new \DBAL\QueryBuilder\Node\SubQueryNode(
            (new Query())
                ->from('posts')
                ->where(['posts.user_id__eqf' => 'u.id']),
            'cnt',
            ['COUNT(*)']
        );

        $msg = (new Query())
            ->from('users u')
            ->buildSelect($subField);

        $this->assertEquals(
            'SELECT (SELECT COUNT(*) FROM posts WHERE "posts"."user_id" = "u"."id") cnt FROM users u',
            $msg->readMessage()
        );
    }
}
