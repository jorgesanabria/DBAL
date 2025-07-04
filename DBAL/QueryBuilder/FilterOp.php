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
}
