<?php
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;
use Exception;

class UnitOfWorkMiddleware implements MiddlewareInterface, CrudAwareMiddlewareInterface
{
    private $tx;
    private $news = [];
    private $dirty = [];
    private $delete = [];

    public function __construct(TransactionMiddleware $tx)
    {
        $this->tx = $tx;
    }

    public function __invoke(MessageInterface $msg): void
    {
        // no-op
    }

    public function registerNew(string $table, array $data): void
    {
        $this->news[] = ['table' => $table, 'data' => $data];
    }

    public function registerDirty(string $table, array $data, array $where): void
    {
        $this->dirty[] = ['table' => $table, 'data' => $data, 'where' => $where];
    }

    public function registerDelete(string $table, array $where): void
    {
        $this->delete[] = ['table' => $table, 'where' => $where];
    }

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

    private function clear(): void
    {
        $this->news = [];
        $this->dirty = [];
        $this->delete = [];
    }
}
