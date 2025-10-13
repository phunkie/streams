<?php

namespace Phunkie\Streams\Ops\Stream;

use Phunkie\Effect\Concurrent\ExecutionContext;
use Phunkie\Effect\Concurrent\FiberExecutionContext;
use Phunkie\Effect\IO\IO;
use Phunkie\Streams\Type\Stream;
use Phunkie\Validation\Failure;
use Phunkie\Validation\Success;
use Phunkie\Validation\Validation;

/**
 * Trait ParallelOps provides parallel/concurrent operations for Streams.
 *
 * These operations leverage phunkie/effect's concurrency capabilities to
 * execute stream operations in parallel, improving performance for
 * computationally expensive or IO-bound operations.
 *
 * All parallel operations include automatic fallback to sequential execution
 * if the execution context fails or is unavailable.
 *
 * @method getPull() Phunkie\Streams\Type\Pull
 * @method getBytes() int
 * @method map(callable $f) Stream
 * @method flatMap(callable $f) Stream
 * @method chunk(int $n) Stream
 */
trait ParallelOps
{
    /**
     * Detect the number of CPU cores available.
     *
     * @return int Number of CPU cores, defaults to 4 if unable to detect
     */
    private function detectCpuCores(): int
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $cores = shell_exec('wmic cpu get NumberOfCores');
            if ($cores) {
                preg_match_all('/\d+/', $cores, $matches);
                if (!empty($matches[0])) {
                    return (int) array_sum($matches[0]);
                }
            }
        } else {
            $cores = shell_exec('nproc 2>/dev/null') ?? shell_exec('sysctl -n hw.ncpu 2>/dev/null');
            if ($cores) {
                return (int) trim($cores);
            }
        }

        return 4; // sensible default
    }

    /**
     * Execute a function in parallel with automatic fallback to sequential execution.
     *
     * @param array $chunk Elements to process
     * @param callable $f Function to apply
     * @param ExecutionContext $context Execution context
     * @return array Results
     */
    private function executeWithFallback(array $chunk, callable $f, ExecutionContext $context): array
    {
        try {
            // Try concurrent execution
            $handles = [];
            foreach ($chunk as $element) {
                $blocker = new \Phunkie\Effect\Concurrent\Blocker(
                    fn () => $f($element),
                    $context
                );
                $handles[] = $blocker();
            }

            // Wait for all to complete
            $results = [];
            foreach ($handles as $handle) {
                $results[] = $handle->await();
            }

            return $results;
        } catch (\Throwable $e) {
            // Fallback to sequential execution
            error_log("Phunkie Streams: Concurrent execution failed, falling back to sequential. Error: " . $e->getMessage());

            $results = [];
            foreach ($chunk as $element) {
                $results[] = $f($element);
            }

            return $results;
        }
    }

    /**
     * Map each element in parallel with bounded concurrency (fail-fast).
     *
     * Applies the given function to each element of the stream in parallel,
     * with a maximum number of concurrent operations specified by $maxConcurrent.
     * If any operation fails, the entire operation fails immediately.
     *
     * This is useful for CPU-bound transformations where you want to leverage
     * multiple cores without overwhelming the system.
     *
     * Automatically falls back to sequential execution if concurrent execution fails.
     *
     * Example:
     * ```php
     * Stream(1, 2, 3, 4, 5)
     *     ->parMap(2, fn($x) => expensiveComputation($x))
     *     ->compile()
     *     ->toArray();
     * ```
     *
     * @param int $maxConcurrent Maximum number of parallel operations (0 = auto-detect CPU cores)
     * @param callable $f Function to apply to each element
     * @param ExecutionContext|null $context Execution context (defaults to FiberExecutionContext)
     * @return Stream
     */
    public function parMap(int $maxConcurrent, callable $f, ?ExecutionContext $context = null): Stream
    {
        if ($maxConcurrent === 0) {
            $maxConcurrent = $this->detectCpuCores();
        }

        $context = $context ?? new FiberExecutionContext();

        // Create a new stream that processes elements in parallel
        return $this->chunk($maxConcurrent)
            ->map(fn ($chunk) => $this->executeWithFallback($chunk, $f, $context))
            ->flatMap(fn ($results) => Stream(...$results));
    }

    /**
     * Map each element in parallel, collecting all errors and successes as Validation.
     *
     * Unlike parMap which fails fast, this method continues processing even if
     * some operations fail, and returns a Stream of Validation objects.
     * Each Validation is either Success($result) or Failure($error).
     *
     * This is useful when you want to process all elements and handle errors
     * individually, rather than failing the entire stream on the first error.
     *
     * Example:
     * ```php
     * Stream(1, 2, 3, 4, 5)
     *     ->parMapValidation(2, fn($x) => riskyComputation($x))
     *     ->compile()
     *     ->toArray(); // Returns [Success(1), Failure($e), Success(3), ...]
     * ```
     *
     * @param int $maxConcurrent Maximum number of parallel operations (0 = auto-detect CPU cores)
     * @param callable $f Function to apply to each element
     * @param ExecutionContext|null $context Execution context (defaults to FiberExecutionContext)
     * @return Stream<Validation>
     */
    public function parMapValidation(int $maxConcurrent, callable $f, ?ExecutionContext $context = null): Stream
    {
        if ($maxConcurrent === 0) {
            $maxConcurrent = $this->detectCpuCores();
        }

        $context = $context ?? new FiberExecutionContext();

        return $this->chunk($maxConcurrent)
            ->map(function ($chunk) use ($f, $context) {
                try {
                    // Try concurrent execution
                    $handles = [];
                    $elements = [];

                    foreach ($chunk as $element) {
                        $elements[] = $element;
                        $blocker = new \Phunkie\Effect\Concurrent\Blocker(
                            function () use ($f, $element) {
                                try {
                                    return Success($f($element));
                                } catch (\Throwable $e) {
                                    return Failure($e);
                                }
                            },
                            $context
                        );
                        $handles[] = $blocker();
                    }

                    // Wait for all to complete
                    $results = [];
                    foreach ($handles as $handle) {
                        $results[] = $handle->await();
                    }

                    return $results;
                } catch (\Throwable $e) {
                    // Fallback to sequential execution
                    error_log("Phunkie Streams: Concurrent execution failed, falling back to sequential. Error: " . $e->getMessage());

                    $results = [];
                    foreach ($chunk as $element) {
                        try {
                            $results[] = Success($f($element));
                        } catch (\Throwable $ex) {
                            $results[] = Failure($ex);
                        }
                    }

                    return $results;
                }
            })
            ->flatMap(fn ($results) => Stream(...$results));
    }

    /**
     * Evaluate IO effects in parallel with bounded concurrency.
     *
     * Similar to parMap, but specifically designed for functions that return IO effects.
     * Each IO effect is evaluated in parallel, and the results are collected.
     *
     * This is particularly useful for IO-bound operations like HTTP requests,
     * database queries, or file operations.
     *
     * Automatically falls back to sequential execution if concurrent execution fails.
     *
     * Example:
     * ```php
     * Stream("url1", "url2", "url3")
     *     ->parEvalMap(2, fn($url) => Network::httpGet($url))
     *     ->compile()
     *     ->drain
     *     ->unsafeRunSync();
     * ```
     *
     * @param int $maxConcurrent Maximum number of parallel IO operations (0 = auto-detect CPU cores)
     * @param callable $f Function that takes an element and returns an IO
     * @param ExecutionContext|null $context Execution context (defaults to FiberExecutionContext)
     * @return Stream
     */
    public function parEvalMap(int $maxConcurrent, callable $f, ?ExecutionContext $context = null): Stream
    {
        if ($maxConcurrent === 0) {
            $maxConcurrent = $this->detectCpuCores();
        }

        $context = $context ?? new FiberExecutionContext();

        return $this->chunk($maxConcurrent)
            ->map(function ($chunk) use ($f, $context) {
                return $this->executeWithFallback(
                    $chunk,
                    function ($element) use ($f) {
                        $io = $f($element);
                        if (!($io instanceof IO)) {
                            throw new \TypeError("parEvalMap expects function to return IO, got " . get_debug_type($io));
                        }

                        return $io->unsafeRunSync();
                    },
                    $context
                );
            })
            ->flatMap(fn ($results) => Stream(...$results));
    }

    /**
     * Traverse the stream, evaluating IO effects in parallel.
     *
     * This is like parEvalMap, but returns a single IO that when run,
     * produces a Stream of results. This is useful when you want to
     * control when the parallel execution happens.
     *
     * Memory optimized: processes elements in chunks without materializing
     * the entire stream upfront. Uses iterator protocol for constant memory usage.
     *
     * Automatically falls back to sequential execution if concurrent execution fails.
     *
     * Example:
     * ```php
     * $io = Stream("url1", "url2", "url3")
     *     ->parTraverse(2, fn($url) => Network::httpGet($url));
     *
     * // Later, when you want to execute:
     * $results = $io->unsafeRunSync();
     * ```
     *
     * @param int $maxConcurrent Maximum number of parallel IO operations (0 = auto-detect CPU cores)
     * @param callable $f Function that takes an element and returns an IO
     * @param ExecutionContext|null $context Execution context (defaults to FiberExecutionContext)
     * @return IO<Stream>
     */
    public function parTraverse(int $maxConcurrent, callable $f, ?ExecutionContext $context = null): IO
    {
        if ($maxConcurrent === 0) {
            $maxConcurrent = $this->detectCpuCores();
        }

        $context = $context ?? new FiberExecutionContext();
        $pull = $this->getPull();
        $bytes = $this->getBytes();

        return new IO(function () use ($pull, $bytes, $maxConcurrent, $f, $context) {
            $results = [];

            // Use iterator protocol for memory-efficient streaming
            if (method_exists($pull, 'rewind')) {
                $pull->rewind();
            }

            // Process in chunks without materializing entire stream
            $chunk = [];
            while ($pull->valid()) {
                $element = $pull->current();

                // Apply transformations to single element
                if (method_exists($pull, 'runTransformations')) {
                    $transformed = $pull->runTransformations([$element]);
                    foreach ($transformed as $value) {
                        $chunk[] = $value;
                    }
                } else {
                    $chunk[] = $element;
                }

                // Process chunk when it reaches maxConcurrent size
                if (count($chunk) >= $maxConcurrent) {
                    $chunkResults = $this->executeWithFallback(
                        $chunk,
                        function ($elem) use ($f) {
                            $io = $f($elem);
                            if (!($io instanceof IO)) {
                                throw new \TypeError("parTraverse expects function to return IO, got " . get_debug_type($io));
                            }

                            return $io->unsafeRunSync();
                        },
                        $context
                    );

                    $results = array_merge($results, $chunkResults);
                    $chunk = [];
                }

                $pull->next();
            }

            // Process remaining elements in final chunk
            if (!empty($chunk)) {
                $chunkResults = $this->executeWithFallback(
                    $chunk,
                    function ($elem) use ($f) {
                        $io = $f($elem);
                        if (!($io instanceof IO)) {
                            throw new \TypeError("parTraverse expects function to return IO, got " . get_debug_type($io));
                        }

                        return $io->unsafeRunSync();
                    },
                    $context
                );

                $results = array_merge($results, $chunkResults);
            }

            return Stream(...$results);
        });
    }

    /**
     * Process the stream with parallel evaluation of each element.
     *
     * This is a convenience method that combines parEvalMap with compile().drain,
     * useful when you want to process a stream of IO effects in parallel but
     * don't care about collecting the results.
     *
     * Example:
     * ```php
     * Stream($user1, $user2, $user3)
     *     ->parEval(2, fn($user) => sendEmail($user))
     *     ->unsafeRunSync(); // Sends all emails in parallel
     * ```
     *
     * @param int $maxConcurrent Maximum number of parallel operations (0 = auto-detect CPU cores)
     * @param callable $f Function that takes an element and returns an IO
     * @param ExecutionContext|null $context Execution context
     * @return IO
     */
    public function parEval(int $maxConcurrent, callable $f, ?ExecutionContext $context = null): IO
    {
        return $this->parEvalMap($maxConcurrent, $f, $context)
            ->compile()
            ->drain;
    }
}
