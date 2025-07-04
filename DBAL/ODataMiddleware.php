<?php
namespace DBAL;

use DBAL\QueryBuilder\DynamicFilterBuilder;
use DBAL\QueryBuilder\MessageInterface;
use DBAL\QueryBuilder\Node\FilterNode;

/**
 * Middleware that parses basic OData query strings and applies them to a Crud instance.
 */
class ODataMiddleware implements MiddlewareInterface, CrudAwareMiddlewareInterface
{
    private array $selectFields = [];
    private ?Crud $crud = null;

    public function __invoke(MessageInterface $msg): void
    {
        // no-op
    }

    /**
     * Attach this middleware to a Crud instance and keep a reference to it.
     */
    public function attach(Crud $crud): Crud
    {
        $crud = $crud->withMiddleware($this);
        $this->crud = $crud;
        return $crud;
    }

    /**
     * Apply the given OData query string to the provided Crud object.
     */
    public function apply(Crud $crud, string $query): Crud
    {
        parse_str($query, $params);
        $result = clone $crud;

        if (isset($params['$top'])) {
            $result = $result->limit((int)$params['$top']);
        }
        if (isset($params['$skip'])) {
            $result = $result->offset((int)$params['$skip']);
        }
        if (isset($params['$orderby'])) {
            foreach (explode(',', $params['$orderby']) as $part) {
                $part = trim($part);
                if ($part === '') {
                    continue;
                }
                $pieces = preg_split('/\s+/', $part);
                $field  = $pieces[0];
                $dir    = strtoupper($pieces[1] ?? 'ASC');
                $result = $dir === 'DESC' ? $result->desc($field) : $result->asc($field);
            }
        }
        if (isset($params['$filter'])) {
            $node   = $this->parseFilter($params['$filter']);
            $result = $result->where($node);
        }
        if (isset($params['$select'])) {
            $this->selectFields = array_map('trim', explode(',', $params['$select']));
        } else {
            $this->selectFields = [];
        }

        return $result;
    }

    /**
     * Return fields parsed from $select.
     */
    public function getFields(): array
    {
        return $this->selectFields;
    }

    /**
     * Apply an OData query string to the attached Crud and return the results.
     */
    public function query(string $odata): array
    {
        if (!$this->crud) {
            throw new \LogicException('ODataMiddleware not attached to Crud');
        }

        $crud = $this->apply($this->crud, $odata);
        $fields = $this->selectFields;
        return iterator_to_array($crud->select(...$fields));
    }

    private function parseFilter(string $expr): FilterNode
    {
        $tokens  = $this->tokenize($expr);
        $pos     = 0;
        $ast     = $this->parseOr($tokens, $pos);
        $builder = new DynamicFilterBuilder();
        $this->buildFilter($ast, $builder);
        return $builder->toNode();
    }

    private function tokenize(string $expr): array
    {
        $pattern = "/'(?:''|[^'])*'|\\(|\\)|\b(?:and|or|eq|ne|gt|ge|lt|le)\b|[A-Za-z_][A-Za-z0-9_]*|\d+\.\d+|\d+/i";
        preg_match_all($pattern, $expr, $m);
        return $m[0];
    }

    private function parseOr(array $tokens, int &$pos)
    {
        $node = $this->parseAnd($tokens, $pos);
        while ($pos < count($tokens) && strtolower($tokens[$pos]) === 'or') {
            $pos++;
            $right = $this->parseAnd($tokens, $pos);
            $node  = ['op' => 'or', 'left' => $node, 'right' => $right];
        }
        return $node;
    }

    private function parseAnd(array $tokens, int &$pos)
    {
        $node = $this->parseFactor($tokens, $pos);
        while ($pos < count($tokens) && strtolower($tokens[$pos]) === 'and') {
            $pos++;
            $right = $this->parseFactor($tokens, $pos);
            $node  = ['op' => 'and', 'left' => $node, 'right' => $right];
        }
        return $node;
    }

    private function parseFactor(array $tokens, int &$pos)
    {
        if ($tokens[$pos] === '(') {
            $pos++;
            $node = $this->parseOr($tokens, $pos);
            if (isset($tokens[$pos]) && $tokens[$pos] === ')') {
                $pos++;
            }
            return $node;
        }
        return $this->parseCondition($tokens, $pos);
    }

    private function parseCondition(array $tokens, int &$pos)
    {
        $field = $tokens[$pos++] ?? '';
        $op    = strtolower($tokens[$pos++] ?? 'eq');
        $value = $tokens[$pos++] ?? '';

        if ($value !== '' && ($value[0] === "'" || $value[0] === '"')) {
            $value = substr($value, 1, -1);
        } elseif (is_numeric($value)) {
            $value = $value + 0;
        }

        return ['field' => $field, 'op' => $op, 'value' => $value];
    }

    private function buildFilter($ast, DynamicFilterBuilder $b): void
    {
        if (isset($ast['left'])) {
            $group = $ast['op'] === 'and' ? 'andGroup' : 'orGroup';
            $next  = $ast['op'] === 'and' ? 'andNext' : 'orNext';
            $b->$group(function($g) use ($ast, $next) {
                $this->buildFilter($ast['left'], $g);
                $g->$next();
                $this->buildFilter($ast['right'], $g);
            });
            return;
        }
        $method = $ast['field'] . '__' . $ast['op'];
        $b->$method($ast['value']);
    }
}
