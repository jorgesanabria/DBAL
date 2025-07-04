<?php
declare(strict_types=1);
namespace DBAL;

use DBAL\QueryBuilder\MessageInterface;
use Generator;

/**
 * Middleware providing RxJS-like helpers for streams.
 */
class RxMiddleware implements MiddlewareInterface, CrudAwareMiddlewareInterface
{
    public function __invoke(MessageInterface $msg): void
    {
        // no-op
    }

    /**
     * Apply a mapping function to each yielded row.
     */
    public function map(Crud $crud, callable $fn, ...$fields): Generator
    {
        foreach ($crud->stream(...$fields) as $row) {
            yield $fn($row);
        }
    }

    /**
     * Yield only rows that satisfy the predicate.
     */
    public function filter(Crud $crud, callable $fn, ...$fields): Generator
    {
        foreach ($crud->stream(...$fields) as $row) {
            if ($fn($row)) {
                yield $row;
            }
        }
    }

    /**
     * Reduce all rows into a single value.
     */
    public function reduce(Crud $crud, callable $fn, $initial = null, ...$fields)
    {
        $acc = $initial;
        foreach ($crud->stream(...$fields) as $row) {
            $acc = $fn($acc, $row);
        }
        return $acc;
    }

    /**
     * Debounce results by delaying each yield in milliseconds.
     */
    public function debounce(Crud $crud, int $ms, ...$fields): Generator
    {
        foreach ($crud->stream(...$fields) as $row) {
            usleep($ms * 1000);
            yield $row;
        }
    }

    /**
     * Execute an operation catching any errors.
     */
    public function catchError(callable $operation, callable $handler)
    {
        try {
            return $operation();
        } catch (\Throwable $e) {
            return $handler($e);
        }
    }

    /**
     * Retry an operation the given number of times.
     */
    public function retry(callable $operation, int $times = 1, int $delayMs = 0)
    {
        $attempts = 0;
        while (true) {
            try {
                return $operation();
            } catch (\Throwable $e) {
                $attempts++;
                if ($attempts > $times) {
                    throw $e;
                }
                if ($delayMs > 0) {
                    usleep($delayMs * 1000);
                }
            }
        }
    }

    /**
     * Merge multiple generators into one sequence.
     */
    public function merge(Generator ...$gens): Generator
    {
        foreach ($gens as $gen) {
            foreach ($gen as $row) {
                yield $row;
            }
        }
    }

    /**
     * Concatenate generators sequentially.
     */
    public function concat(Generator ...$gens): Generator
    {
        foreach ($gens as $gen) {
            foreach ($gen as $row) {
                yield $row;
            }
        }
    }
}
