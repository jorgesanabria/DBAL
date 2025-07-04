<?php
namespace DBAL;

use PDO;
use DBAL\QueryBuilder\MessageInterface;

/**
 * Clase/Interfaz TransactionMiddleware
 */
class TransactionMiddleware implements MiddlewareInterface
{
    private PDO $pdo;
    private array $log = [];
    private bool $inTx = false;

/**
 * __construct
 * @param PDO $pdo
 * @return void
 */

    public function __construct(private PDO $pdo)
    {    }

/**
 * __invoke
 * @param MessageInterface $msg
 * @return void
 */

    public function __invoke(MessageInterface $msg): void
    {
        // record transaction state on each query
        $this->log[] = $this->pdo->inTransaction();
        $this->inTx = $this->pdo->inTransaction();
    }

/**
 * begin
 * @return void
 */

    public function begin(): void
    {
        $this->pdo->beginTransaction();
        $this->inTx = true;
    }

/**
 * commit
 * @return void
 */

    public function commit(): void
    {
        $this->pdo->commit();
        $this->inTx = false;
    }

/**
 * rollback
 * @return void
 */

    public function rollback(): void
    {
        $this->pdo->rollBack();
        $this->inTx = false;
    }

/**
 * getLog
 * @return array
 */

    public function getLog(): array
    {
        return $this->log;
    }

/**
 * inTransaction
 * @return bool
 */

    public function inTransaction(): bool
    {
        return $this->inTx;
    }
}
