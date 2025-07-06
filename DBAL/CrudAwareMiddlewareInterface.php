<?php
declare(strict_types=1);
namespace DBAL;

/**
 * Marker interface for middlewares that need access to the Crud instance.
 */
interface CrudAwareMiddlewareInterface extends MiddlewareInterface
{
}
