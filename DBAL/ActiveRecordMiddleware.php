<?php
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;

/**
 * Clase/Interfaz ActiveRecordMiddleware
 */
class ActiveRecordMiddleware implements MiddlewareInterface
{
/**
 * __invoke
 * @param MessageInterface $msg
 * @return void
 */

        public function __invoke(MessageInterface $msg): void
        {
                // no-op
        }

/**
 * attach
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
