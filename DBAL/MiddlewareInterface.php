<?php
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;

interface MiddlewareInterface
{
    public function __invoke(MessageInterface $message): void;
}
