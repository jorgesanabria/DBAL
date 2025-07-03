<?php
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;

class ActiveRecordMiddleware implements MiddlewareInterface
{
        public function __invoke(MessageInterface $msg): void
        {
                // no-op
        }

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
