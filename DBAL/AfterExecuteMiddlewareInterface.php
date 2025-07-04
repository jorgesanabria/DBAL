<?php
declare(strict_types=1);
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;

interface AfterExecuteMiddlewareInterface
{
    public function afterExecute(MessageInterface $msg, float $time): void;
}
