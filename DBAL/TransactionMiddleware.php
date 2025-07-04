<?php
namespace DBAL;

use PDO;
use DBAL\QueryBuilder\MessageInterface;

/**
 * Middleware that exposes helpers to manage database transactions.
 *
 * It records whether queries were executed inside a transaction and provides
 * `begin()`, `commit()` and `rollback()` methods.
 */
class TransactionMiddleware implements MiddlewareInterface
{
    /**
     * Log of transaction status for each executed message.
     *
     * @var bool[]
     */
    private array $log = [];

    /** Indicates whether a transaction is currently active. */
    private bool $inTx = false;

    /**
     * Create a new instance bound to a PDO connection.
     *
     * @param PDO $pdo Database connection used for transaction control.
     */
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Record the transaction state for the executed message.
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
     * Get the transaction status recorded for each executed message.
     *
     * @return bool[]
     */
    public function getLog(): array
    {
        return $this->log;
    }

    /**
     * Determine if the connection is currently inside a transaction.
     */
    public function inTransaction(): bool
    {
        return $this->inTx;
    }
}
