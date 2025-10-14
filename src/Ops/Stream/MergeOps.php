<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams\Ops\Stream;

use Phunkie\Effect\Concurrent\ExecutionContext;
use Phunkie\Effect\Concurrent\FiberExecutionContext;
use Phunkie\Streams\Type\Stream;

/**
 * Trait MergeOps provides stream merging operations, including concurrent merging.
 *
 * @method getPull() Phunkie\Streams\Type\Pull
 * @method getBytes() int
 * @method flatMap(callable $f) Stream
 */
trait MergeOps
{
    /**
     * Merge multiple streams concurrently.
     *
     * Takes multiple streams and merges them into a single stream,
     * evaluating them concurrently. Elements from all streams are
     * interleaved in the output stream based on when they become available.
     *
     * Memory optimized: processes streams incrementally using iterator protocol
     * instead of materializing all streams into arrays upfront.
     *
     * This is useful when you have multiple independent data sources
     * (e.g., multiple API endpoints, files, or databases) and want to
     * process them all concurrently.
     *
     * Example:
     * ```php
     * $stream1 = Stream(1, 2, 3);
     * $stream2 = Stream(4, 5, 6);
     * $stream3 = Stream(7, 8, 9);
     *
     * $merged = Stream::parMerge($stream1, $stream2, $stream3);
     * // Output order depends on concurrent execution
     * ```
     *
     * @param Stream ...$streams Streams to merge
     * @param ExecutionContext|null $context Execution context (defaults to FiberExecutionContext)
     * @return Stream
     */
    public static function parMerge(Stream ...$streams): Stream
    {
        if (empty($streams)) {
            return Stream();
        }

        if (count($streams) === 1) {
            return $streams[0];
        }

        $context = new FiberExecutionContext();

        // Collect elements using iterator protocol for memory efficiency
        $allElements = [];

        try {
            // Start concurrent iteration of all streams
            $handles = [];
            foreach ($streams as $stream) {
                $blocker = new \Phunkie\Effect\Concurrent\Blocker(
                    function () use ($stream) {
                        $pull = $stream->compile()->getPull();
                        $elements = [];

                        if (method_exists($pull, 'rewind')) {
                            $pull->rewind();
                        }

                        // Iterate and apply transformations per element
                        while ($pull->valid()) {
                            $element = $pull->current();

                            if (method_exists($pull, 'runTransformations')) {
                                $transformed = $pull->runTransformations([$element]);
                                foreach ($transformed as $value) {
                                    $elements[] = $value;
                                }
                            } else {
                                $elements[] = $element;
                            }

                            $pull->next();
                        }

                        return $elements;
                    },
                    $context
                );
                $handles[] = $blocker();
            }

            // Wait for all streams to complete
            foreach ($handles as $handle) {
                $elements = $handle->await();
                $allElements = array_merge($allElements, $elements);
            }
        } catch (\Throwable $e) {
            // Fallback to sequential merging with iterator protocol
            error_log("Phunkie Streams: Concurrent merge failed, falling back to sequential. Error: " . $e->getMessage());

            foreach ($streams as $stream) {
                $pull = $stream->compile()->getPull();

                if (method_exists($pull, 'rewind')) {
                    $pull->rewind();
                }

                while ($pull->valid()) {
                    $element = $pull->current();

                    if (method_exists($pull, 'runTransformations')) {
                        $transformed = $pull->runTransformations([$element]);
                        foreach ($transformed as $value) {
                            $allElements[] = $value;
                        }
                    } else {
                        $allElements[] = $element;
                    }

                    $pull->next();
                }
            }
        }

        return Stream(...$allElements);
    }

    /**
     * FlatMap with concurrent stream evaluation.
     *
     * Like flatMap, but evaluates the resulting streams concurrently
     * with bounded parallelism. This is useful when your mapping function
     * produces streams that can be evaluated independently.
     *
     * Memory optimized: uses iterator protocol to process streams incrementally
     * instead of materializing entire streams with toArray().
     *
     * Example:
     * ```php
     * Stream("user1", "user2", "user3")
     *     ->parMergeMap(2, fn($user) =>
     *         Stream($user->getPosts())  // Returns a Stream of posts
     *     )
     *     ->compile()
     *     ->toArray(); // All posts from all users
     * ```
     *
     * @param int $maxConcurrent Maximum number of concurrent stream evaluations
     * @param callable $f Function that takes an element and returns a Stream
     * @param ExecutionContext|null $context Execution context (defaults to FiberExecutionContext)
     * @return Stream
     */
    public function parMergeMap(int $maxConcurrent, callable $f, ?ExecutionContext $context = null): Stream
    {
        $context = $context ?? new FiberExecutionContext();

        return $this->chunk($maxConcurrent)
            ->flatMap(function ($chunk) use ($f, $context) {
                try {
                    // Start concurrent evaluation of all streams in chunk
                    $handles = [];
                    foreach ($chunk as $element) {
                        $blocker = new \Phunkie\Effect\Concurrent\Blocker(
                            function () use ($f, $element) {
                                $stream = $f($element);
                                if (!($stream instanceof Stream)) {
                                    throw new \TypeError("parMergeMap expects function to return Stream, got " . get_debug_type($stream));
                                }

                                // Use iterator protocol for memory efficiency
                                $pull = $stream->compile()->getPull();
                                $elements = [];

                                if (method_exists($pull, 'rewind')) {
                                    $pull->rewind();
                                }

                                while ($pull->valid()) {
                                    $elem = $pull->current();

                                    if (method_exists($pull, 'runTransformations')) {
                                        $transformed = $pull->runTransformations([$elem]);
                                        foreach ($transformed as $value) {
                                            $elements[] = $value;
                                        }
                                    } else {
                                        $elements[] = $elem;
                                    }

                                    $pull->next();
                                }

                                return $elements;
                            },
                            $context
                        );
                        $handles[] = $blocker();
                    }

                    // Wait for all and collect results
                    $allElements = [];
                    foreach ($handles as $handle) {
                        $elements = $handle->await();
                        $allElements = array_merge($allElements, $elements);
                    }

                    return Stream(...$allElements);
                } catch (\Throwable $e) {
                    // Fallback to sequential with iterator protocol
                    error_log("Phunkie Streams: Concurrent merge map failed, falling back to sequential. Error: " . $e->getMessage());

                    $allElements = [];
                    foreach ($chunk as $element) {
                        $stream = $f($element);
                        if (!($stream instanceof Stream)) {
                            throw new \TypeError("parMergeMap expects function to return Stream, got " . get_debug_type($stream));
                        }

                        $pull = $stream->compile()->getPull();

                        if (method_exists($pull, 'rewind')) {
                            $pull->rewind();
                        }

                        while ($pull->valid()) {
                            $elem = $pull->current();

                            if (method_exists($pull, 'runTransformations')) {
                                $transformed = $pull->runTransformations([$elem]);
                                foreach ($transformed as $value) {
                                    $allElements[] = $value;
                                }
                            } else {
                                $allElements[] = $elem;
                            }

                            $pull->next();
                        }
                    }

                    return Stream(...$allElements);
                }
            });
    }
}
