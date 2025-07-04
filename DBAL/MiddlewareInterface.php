<?php
declare(strict_types=1);
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;

/**
 * Clase/Interfaz MiddlewareInterface
 */
interface MiddlewareInterface
{
/**
 * __invoke
 * @param MessageInterface $message
 * @return void
 */

    public function __invoke(MessageInterface $message): void;
}
