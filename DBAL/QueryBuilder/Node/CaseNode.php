<?php
declare(strict_types=1);
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\MessageInterface;
use DBAL\QueryBuilder\Message;

/**
 * Node that builds a SQL CASE WHEN expression.
 */
class CaseNode extends FieldNode
{
    protected array $cases = [];
    protected mixed $else = null;
    protected ?string $alias = null;

    /**
     * Add a WHEN condition.
     *
     * @param string $condition SQL condition expression
     * @param string $result    Result expression
     * @return $this
     */
    public function when(string $condition, string $result)
    {
        $this->cases[] = [$condition, $result];
        return $this;
    }

    /**
     * Define the ELSE part of the CASE expression.
     */
    public function else(string $result)
    {
        $this->else = $result;
        return $this;
    }

    /**
     * Set an alias for the resulting expression.
     */
    public function as(string $alias)
    {
        $this->alias = $alias;
        return $this;
    }

    public function send(MessageInterface $message)
    {
        $msg = new Message($message->type());
        $msg = $msg->insertAfter('CASE');
        foreach ($this->cases as [$cond, $res]) {
            $msg = $msg->insertAfter(sprintf('WHEN %s THEN %s', $cond, $res));
        }
        if ($this->else !== null) {
            $msg = $msg->insertAfter(sprintf('ELSE %s', $this->else));
        }
        $msg = $msg->insertAfter('END');
        if ($this->alias) {
            $msg = $msg->insertAfter(sprintf('AS %s', $this->alias));
        }
        return $message->insertAfter($msg->readMessage(), MessageInterface::SEPARATOR_COMMA);
    }
}
