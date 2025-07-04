<?php
declare(strict_types=1);
namespace DBAL\QueryBuilder;

enum JoinType: string
{
        case INNER = 'INNER JOIN';
        case LEFT  = 'LEFT JOIN';
        case RIGHT = 'RIGHT JOIN';
}
