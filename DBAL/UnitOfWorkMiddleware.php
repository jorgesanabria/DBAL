<?php
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;
use Exception;

/**
 * Clase/Interfaz UnitOfWorkMiddleware
 */
class UnitOfWorkMiddleware implements MiddlewareInterface, CrudAwareMiddlewareInterface
{
    private array $news = [];
    private array $dirty = [];
    private array $delete = [];

/**
 * __construct
 * @param TransactionMiddleware $tx
 * @return void
 */

    public function __construct(private TransactionMiddleware $tx)
    {    }

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
 * registerNew
 * @param Crud $crud
 * @param string $table
 * @param array $data
 * @return void
 */

    public function registerNew(Crud $crud, string $table, array $data): void
    {
        $this->news[] = ['table' => $table, 'data' => $data];
    }

/**
 * registerDirty
 * @param Crud $crud
 * @param string $table
 * @param array $data
 * @param array $where
 * @return void
 */

    public function registerDirty(Crud $crud, string $table, array $data, array $where): void
    {
        $this->dirty[] = ['table' => $table, 'data' => $data, 'where' => $where];
    }

/**
 * registerDelete
 * @param Crud $crud
 * @param string $table
 * @param array $where
 * @return void
 */

    public function registerDelete(Crud $crud, string $table, array $where): void
    {
        $this->delete[] = ['table' => $table, 'where' => $where];
    }

/**
 * commit
 * @param Crud $crud
 * @return void
 */

    public function commit(Crud $crud): void
    {
        $this->tx->begin();
        try {
            foreach ($this->news as $n) {
                $crud->from($n['table'])->insert($n['data']);
            }
            foreach ($this->dirty as $u) {
                $crud->from($u['table'])->where($u['where'])->update($u['data']);
            }
            foreach ($this->delete as $d) {
                $crud->from($d['table'])->where($d['where'])->delete();
            }
            $this->tx->commit();
        } catch (Exception $e) {
            $this->tx->rollback();
            throw $e;
        } finally {
            $this->clear();
        }
    }

/**
 * clear
 * @return void
 */

    private function clear(): void
    {
        $this->news = [];
        $this->dirty = [];
        $this->delete = [];
    }
}
