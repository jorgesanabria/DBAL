<?php
declare(strict_types=1);
namespace DBAL\QueryBuilder\Node;

use DBAL\QueryBuilder\Query;
use DBAL\QueryBuilder\MessageInterface;

/**
 * Node representing a subquery used inside a field list.
 * It wraps another Query instance and forwards its bound values.
 */
class SubQueryNode extends FieldNode
{
    protected Query $query;
    protected array $fields;
    protected ?string $alias;

    /**
     * @param Query       $query  Query object to embed as a subquery
     * @param string|null $alias  Optional alias for the subquery
     * @param array       $fields Fields to select in the subquery
     */
    public function __construct(Query $query, ?string $alias = null, array $fields = [])
    {
        parent::__construct('');
        $this->query  = $query;
        $this->alias  = $alias;
        $this->fields = $fields;
    }

    public function send(MessageInterface $message)
    {
        $sub = $this->query->buildSelect(...$this->fields);
        $sql = '(' . $sub->readMessage() . ')';
        if ($this->alias) {
            $sql .= ' ' . $this->alias;
        }
        return $message->insertAfter($sql, MessageInterface::SEPARATOR_COMMA)
                        ->addValues($sub->getValues());
    }
}
