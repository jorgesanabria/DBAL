<?php
declare(strict_types=1);

namespace DBAL\QueryBuilder;

/**
 * Enumeration of available filter operations.
 */
enum FilterOp: string
{
    case EQ = 'eq';
    case NE = 'ne';
    case GT = 'gt';
    case LT = 'lt';
    case GE = 'ge';
    case LE = 'le';
    case IN = 'in';
    case BETWEEN = 'between';
    case EQF = 'eqf';
    case LIKE = 'like';
    case STARTS_WITH = 'startsWith';
    case ENDS_WITH = 'endsWith';
    case CONTAINS = 'contains';
    case NOT_LIKE = 'notLike';
    case IS_NULL = 'isNull';
    case NOT_NULL = 'notNull';
    case NOT_IN = 'notIn';
    case NOT_BETWEEN = 'notBetween';
    case ILIKE = 'iLike';
    case REGEX = 'regex';
    case EXISTS = 'exists';
    case NOT_EXISTS = 'notExists';
    case BETWEEN_INCLUSIVE = 'betweenInclusive';
}
