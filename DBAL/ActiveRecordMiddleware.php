<?php
declare(strict_types=1);
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;

/**
 * Middleware that wraps result rows in ActiveRecord objects.
 */
class ActiveRecordMiddleware implements MiddlewareInterface
{
/**
 * Middleware hook executed for each query. Currently does nothing.
 *
 * @param MessageInterface $msg
 */
        public function __invoke(MessageInterface $msg): void
        {
                // no-op
        }

/**
 * Attach this middleware and map rows to ActiveRecord instances.
 *
 * @param Crud $crud
 * @return Crud
 */

        public function attach(Crud $crud): Crud
        {
                $ref = null;
                $crud = $crud->map(function(array $row) use (&$ref) {
                        return new ActiveRecord($ref, $row);
                });
                $crud = $crud->withMiddleware($this);
                $ref = $crud;
                return $crud;
        }
}
