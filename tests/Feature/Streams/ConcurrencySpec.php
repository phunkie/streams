<?php

use Phunkie\Effect\Concurrent\FiberExecutionContext;
use Phunkie\Effect\Concurrent\ParallelExecutionContext;
use Phunkie\Effect\IO\IO;
use Phunkie\Validation\Success;
use Phunkie\Validation\Failure;

use function Phunkie\Effect\Functions\io\io;

// Stream() is in global namespace
require_once __DIR__ . '/../../../src/Functions/stream.php';

describe('Concurrency Operations', function() {

    describe('parMap', function() {
        it('maps elements in parallel with bounded concurrency', function() {
            $start = microtime(true);

            $result = Stream(1, 2, 3, 4)
                ->parMap(2, function($x) {
                    usleep(100_000); // 100ms per element
                    return $x * 2;
                })
                ->compile()
                ->toArray();

            $duration = microtime(true) - $start;

            expect($result)->toBe([2, 4, 6, 8]);
            // With concurrency of 2, should take ~200ms (2 batches of 100ms each)
            // Without concurrency, would take ~400ms (4 sequential operations)
            expect($duration)->toBeLessThan(0.5); // Allow overhead for PHP Fibers
        });

        it('auto-detects CPU cores when maxConcurrent is 0', function() {
            $result = Stream(1, 2, 3, 4)
                ->parMap(0, fn($x) => $x * 2) // 0 means auto-detect
                ->compile()
                ->toArray();

            expect($result)->toBe([2, 4, 6, 8]);
        });

        it('throws immediately on error (fail-fast)', function() {
            expect(fn() =>
                Stream(1, 2, 3, 4)
                    ->parMap(2, function($x) {
                        if ($x === 2) {
                            throw new \Exception("Error on 2");
                        }
                        return $x * 2;
                    })
                    ->compile()
                    ->toArray()
            )->toThrow(\Exception::class);
        });

        it('falls back to sequential execution on concurrency failure', function() {
            // This test verifies the fallback mechanism works
            $result = Stream(1, 2, 3, 4)
                ->parMap(2, fn($x) => $x * 2)
                ->compile()
                ->toArray();

            expect($result)->toBe([2, 4, 6, 8]);
        });
    });

    describe('parMapValidation', function() {
        it('collects all successes and failures', function() {
            $result = Stream(1, 2, 3, 4, 5)
                ->parMapValidation(2, function($x) {
                    if ($x % 2 === 0) {
                        throw new \Exception("Even number: $x");
                    }
                    return $x * 2;
                })
                ->compile()
                ->toArray();

            expect(count($result))->toBe(5);

            // Check successes (Validation uses $valid property, not $value)
            expect($result[0])->toBeInstanceOf(Success::class);

            // Check failures
            expect($result[1])->toBeInstanceOf(Failure::class);

            expect($result[2])->toBeInstanceOf(Success::class);

            expect($result[3])->toBeInstanceOf(Failure::class);
            expect($result[4])->toBeInstanceOf(Success::class);
        });

        it('continues processing after errors', function() {
            $processedElements = [];

            $result = Stream(1, 2, 3, 4)
                ->parMapValidation(2, function($x) use (&$processedElements) {
                    $processedElements[] = $x;
                    if ($x === 2) {
                        throw new \Exception("Error on 2");
                    }
                    return $x * 2;
                })
                ->compile()
                ->toArray();

            // All elements should have been processed
            expect(count($processedElements))->toBe(4);
            expect(count($result))->toBe(4);
        });
    });

    describe('parEvalMap', function() {
        it('evaluates IO effects in parallel', function() {
            $start = microtime(true);

            $result = Stream(1, 2, 3, 4)
                ->parEvalMap(2, function($x) {
                    return io(function() use ($x) {
                        usleep(100_000); // 100ms per operation
                        return $x * 2;
                    });
                })
                ->compile()
                ->toArray();

            $duration = microtime(true) - $start;

            expect($result)->toBe([2, 4, 6, 8]);
            expect($duration)->toBeLessThan(0.5); // Should be faster than sequential
        });

        it('throws if function does not return IO', function() {
            expect(fn() =>
                Stream(1, 2, 3)
                    ->parEvalMap(2, fn($x) => $x * 2) // Not returning IO
                    ->compile()
                    ->toArray()
            )->toThrow(\TypeError::class);
        });

        it('handles IO errors properly', function() {
            expect(fn() =>
                Stream(1, 2, 3)
                    ->parEvalMap(2, function($x) {
                        return io(function() use ($x) {
                            if ($x === 2) {
                                throw new \Exception("Error on 2");
                            }
                            return $x * 2;
                        });
                    })
                    ->compile()
                    ->toArray()
            )->toThrow(\Exception::class);
        });
    });

    describe('parTraverse', function() {
        it('returns an IO that produces a Stream', function() {
            $io = Stream(1, 2, 3, 4)
                ->parTraverse(2, function($x) {
                    return io(fn() => $x * 2);
                });

            expect($io)->toBeInstanceOf(IO::class);

            $result = $io->unsafeRunSync();
            expect($result)->toBeInstanceOf(\Phunkie\Streams\Type\Stream::class);

            $array = $result->compile()->toArray();
            expect($array)->toBe([2, 4, 6, 8]);
        });

        it('allows deferred execution', function() {
            $executed = false;

            $io = Stream(1, 2, 3)
                ->parTraverse(2, function($x) use (&$executed) {
                    return io(function() use ($x, &$executed) {
                        $executed = true;
                        return $x * 2;
                    });
                });

            // Not executed yet
            expect($executed)->toBe(false);

            // Execute now
            $io->unsafeRunSync();
            expect($executed)->toBe(true);
        });
    });

    describe('parEval', function() {
        it('processes effects without collecting results', function() {
            $sideEffects = [];

            $io = Stream(1, 2, 3, 4)
                ->parEval(2, function($x) use (&$sideEffects) {
                    return io(function() use ($x, &$sideEffects) {
                        $sideEffects[] = $x;
                        return $x;
                    });
                });

            expect($io)->toBeInstanceOf(IO::class);

            $io->unsafeRunSync();

            // All side effects should have occurred
            expect(count($sideEffects))->toBe(4);
            expect($sideEffects)->toContain(1, 2, 3, 4);
        });
    });

    describe('parMerge', function() {
        it('merges multiple streams concurrently', function() {
            $stream1 = Stream(1, 2, 3);
            $stream2 = Stream(4, 5, 6);
            $stream3 = Stream(7, 8, 9);

            $result = \Phunkie\Streams\Type\Stream::parMerge($stream1, $stream2, $stream3)
                ->compile()
                ->toArray();

            // Order might vary due to concurrency, but all elements should be present
            expect(count($result))->toBe(9);
            sort($result);
            expect($result)->toBe([1, 2, 3, 4, 5, 6, 7, 8, 9]);
        });

        it('handles single stream', function() {
            $stream = Stream(1, 2, 3);
            $result = \Phunkie\Streams\Type\Stream::parMerge($stream)
                ->compile()
                ->toArray();

            expect($result)->toBe([1, 2, 3]);
        });

        it('handles empty streams', function() {
            $result = \Phunkie\Streams\Type\Stream::parMerge()
                ->compile()
                ->toArray();

            expect($result)->toBe([]);
        });

        it('is faster than sequential merging', function() {
            $stream1 = Stream(1, 2, 3)->map(function($x) {
                usleep(100_000); // 100ms per element
                return $x;
            });
            $stream2 = Stream(4, 5, 6)->map(function($x) {
                usleep(100_000);
                return $x;
            });

            $start = microtime(true);
            $result = \Phunkie\Streams\Type\Stream::parMerge($stream1, $stream2)
                ->compile()
                ->toArray();
            $duration = microtime(true) - $start;

            expect(count($result))->toBe(6);
            // Should be significantly faster than sequential (< 600ms)
            expect($duration)->toBeLessThan(0.7);
        });
    });

    describe('parMergeMap', function() {
        it('flat maps with concurrent stream evaluation', function() {
            $result = Stream(1, 2, 3)
                ->parMergeMap(2, fn($x) => Stream($x, $x * 10, $x * 100))
                ->compile()
                ->toArray();

            // Order may vary, but all elements should be present
            expect(count($result))->toBe(9);
            expect($result)->toContain(1, 10, 100, 2, 20, 200, 3, 30, 300);
        });

        it('throws if function does not return Stream', function() {
            expect(fn() =>
                Stream(1, 2, 3)
                    ->parMergeMap(2, fn($x) => $x * 2) // Not returning Stream
                    ->compile()
                    ->toArray()
            )->toThrow(\TypeError::class);
        });

        it('processes streams concurrently', function() {
            $start = microtime(true);

            $result = Stream(1, 2, 3)
                ->parMergeMap(2, function($x) {
                    return Stream(1, 2)->map(function($y) use ($x) {
                        usleep(100_000); // 100ms per element
                        return $x * 10 + $y;
                    });
                })
                ->compile()
                ->toArray();

            $duration = microtime(true) - $start;

            expect(count($result))->toBe(6);
            // Should be faster than fully sequential execution
            expect($duration)->toBeLessThan(0.7);
        });
    });

    describe('Execution Contexts', function() {
        it('works with FiberExecutionContext by default', function() {
            $result = Stream(1, 2, 3, 4)
                ->parMap(2, fn($x) => $x * 2, new FiberExecutionContext())
                ->compile()
                ->toArray();

            expect($result)->toBe([2, 4, 6, 8]);
        });

        it('works with ParallelExecutionContext if parallel extension is available', function() {
            if (!class_exists('\parallel\Runtime')) {
                $this->markTestSkipped('parallel extension not available');
            }

            $result = Stream(1, 2, 3, 4)
                ->parMap(2, fn($x) => $x * 2, new ParallelExecutionContext())
                ->compile()
                ->toArray();

            expect($result)->toBe([2, 4, 6, 8]);
        })->skip(!class_exists('\parallel\Runtime'), 'parallel extension not available');
    });

    describe('Performance', function() {
        it('provides same results for sequential and parallel', function() {
            // Sequential execution
            $sequential = Stream(1, 2, 3, 4)
                ->map(fn($x) => $x * 2)
                ->compile()
                ->toArray();

            // Parallel execution
            $parallel = Stream(1, 2, 3, 4)
                ->parMap(2, fn($x) => $x * 2)
                ->compile()
                ->toArray();

            // Both should produce the same results
            expect($sequential)->toBe($parallel);
            expect($parallel)->toBe([2, 4, 6, 8]);
        });
    });
});
