<?php
namespace DBAL;

use PDO;
use DBAL\QueryBuilder\MessageInterface;

class TransactionMiddleware implements MiddlewareInterface
{
    private $pdo;
    private $log = [];
    private $inTx = false;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(MessageInterface $msg): void
    {
        // record transaction state on each query
        $this->log[] = $this->pdo->inTransaction();
        $this->inTx = $this->pdo->inTransaction();
    }

    public function begin(): void
    {
        $this->pdo->beginTransaction();
        $this->inTx = true;
    }

    public function commit(): void
    {
        $this->pdo->commit();
        $this->inTx = false;
    }

    public function rollback(): void
    {
        $this->pdo->rollBack();
        $this->inTx = false;
    }

    public function getLog(): array
    {
        return $this->log;
    }

    public function inTransaction(): bool
    {
        return $this->inTx;
    }
}
