<?php
declare(strict_types=1);
namespace DBAL;

use Psr\Log\LoggerInterface;
use DBAL\QueryBuilder\MessageInterface;

/**
 * Middleware that logs executed SQL statements and their bound values.
 *
 * The constructor accepts either a PSR-3 {@see LoggerInterface} or a callable
 * taking the SQL string and parameters.
 */
class LoggingMiddleware implements MiddlewareInterface
{
    /** @var callable|LoggerInterface */
    private $logger;

    /**
     * Initialise the middleware with a logger or callable.
     *
     * @param callable|LoggerInterface $logger Logger instance or function.
     */
    public function __construct(callable|LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Invoked for each query before execution to log the SQL.
     */
    public function __invoke(MessageInterface $msg): void
    {
        $sql    = $msg->readMessage();
        $values = $msg->getValues();
        if ($this->logger instanceof LoggerInterface) {
            $this->logger->debug($sql, $values);
        } else {
            ($this->logger)($sql, $values);
        }
    }
}
