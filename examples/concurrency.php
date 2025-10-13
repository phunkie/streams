<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Phunkie\Effect\Concurrent\FiberExecutionContext;
use Phunkie\Effect\Concurrent\ParallelExecutionContext;

use function Phunkie\Effect\Functions\io\io;

use Phunkie\Streams\Network;

use function Phunkie\Streams\Stream;

echo "=== Phunkie Streams Concurrency Examples ===\n\n";

// ============================================================================
// Example 1: Basic Parallel Mapping (CPU-bound)
// ============================================================================
echo "1. Basic Parallel Mapping (CPU-bound operations)\n";
echo str_repeat("-", 70) . "\n";

function expensiveComputation(int $x): int
{
    // Simulate CPU-intensive work
    usleep(200_000); // 200ms

    return $x * $x;
}

// Sequential execution
$start = microtime(true);
$sequential = Stream(1, 2, 3, 4, 5, 6)
    ->map(fn ($x) => expensiveComputation($x))
    ->compile()
    ->toArray();
$sequentialTime = microtime(true) - $start;

echo "Sequential result: " . implode(", ", $sequential) . "\n";
echo sprintf("Sequential time: %.2f seconds\n", $sequentialTime);

// Parallel execution with concurrency of 3
$start = microtime(true);
$parallel = Stream(1, 2, 3, 4, 5, 6)
    ->parMap(3, fn ($x) => expensiveComputation($x))
    ->compile()
    ->toArray();
$parallelTime = microtime(true) - $start;

echo "Parallel result: " . implode(", ", $parallel) . "\n";
echo sprintf("Parallel time: %.2f seconds\n", $parallelTime);
echo sprintf("Speedup: %.2fx faster\n\n", $sequentialTime / $parallelTime);

// ============================================================================
// Example 2: Parallel HTTP Requests (IO-bound)
// ============================================================================
echo "2. Parallel HTTP Requests (IO-bound operations)\n";
echo str_repeat("-", 70) . "\n";

$urls = [
    "https://httpbin.org/delay/1",
    "https://httpbin.org/delay/1",
    "https://httpbin.org/delay/1",
];

echo "Fetching " . count($urls) . " URLs with 1 second delay each...\n";

// Sequential would take ~3 seconds
// Parallel should take ~1 second

$start = microtime(true);
$responses = Stream(...$urls)
    ->parEvalMap(3, fn ($url) => Network::httpGet($url))
    ->compile()
    ->drain
    ->unsafeRunSync();
$duration = microtime(true) - $start;

echo sprintf("Completed in %.2f seconds (parallel execution)\n", $duration);
echo "All requests completed concurrently!\n\n";

// ============================================================================
// Example 3: Error Handling with parMapValidation
// ============================================================================
echo "3. Error Handling with parMapValidation\n";
echo str_repeat("-", 70) . "\n";

function riskyOperation(int $x): int
{
    if ($x % 3 === 0) {
        throw new \Exception("Failed on $x (divisible by 3)");
    }

    return $x * 2;
}

$results = Stream(1, 2, 3, 4, 5, 6, 7, 8, 9)
    ->parMapValidation(3, fn ($x) => riskyOperation($x))
    ->compile()
    ->toArray();

$successes = array_filter($results, fn ($v) => $v->isSuccess());
$failures = array_filter($results, fn ($v) => $v->isFailure());

echo "Total operations: " . count($results) . "\n";
echo "Successes: " . count($successes) . "\n";
echo "Failures: " . count($failures) . "\n";

echo "\nSuccessful results: ";
foreach ($successes as $success) {
    echo $success->value . " ";
}

echo "\n\nFailed on values: ";
foreach ($failures as $failure) {
    echo $failure->value->getMessage() . ", ";
}
echo "\n\n";

// ============================================================================
// Example 4: Parallel Stream Merging
// ============================================================================
echo "4. Parallel Stream Merging\n";
echo str_repeat("-", 70) . "\n";

// Simulate three data sources that take time to generate data
$source1 = Stream(1, 2, 3)->map(function ($x) {
    usleep(100_000); // 100ms per item

    return "A$x";
});

$source2 = Stream(4, 5, 6)->map(function ($x) {
    usleep(100_000);

    return "B$x";
});

$source3 = Stream(7, 8, 9)->map(function ($x) {
    usleep(100_000);

    return "C$x";
});

$start = microtime(true);
$merged = \Phunkie\Streams\Type\Stream::parMerge($source1, $source2, $source3)
    ->compile()
    ->toArray();
$duration = microtime(true) - $start;

echo "Merged " . count($merged) . " items from 3 sources\n";
echo "Results: " . implode(", ", $merged) . "\n";
echo sprintf("Time: %.2f seconds (concurrent evaluation)\n\n", $duration);

// ============================================================================
// Example 5: parMergeMap for Hierarchical Data
// ============================================================================
echo "5. parMergeMap for Hierarchical Data Processing\n";
echo str_repeat("-", 70) . "\n";

// Simulate fetching users and their posts
function getUserPosts(int $userId): \Phunkie\Streams\Type\Stream
{
    // Simulate API call
    usleep(100_000); // 100ms

    return Stream("Post{$userId}-1", "Post{$userId}-2", "Post{$userId}-3");
}

$start = microtime(true);
$allPosts = Stream(1, 2, 3, 4)
    ->parMergeMap(2, fn ($userId) => getUserPosts($userId))
    ->compile()
    ->toArray();
$duration = microtime(true) - $start;

echo "Fetched posts from 4 users\n";
echo "Total posts: " . count($allPosts) . "\n";
echo sprintf("Time: %.2f seconds (2 users fetched concurrently)\n\n", $duration);

// ============================================================================
// Example 6: parTraverse for Deferred Execution
// ============================================================================
echo "6. parTraverse for Deferred Execution\n";
echo str_repeat("-", 70) . "\n";

// Build an IO that represents parallel work, but don't execute yet
$io = Stream(1, 2, 3, 4, 5)
    ->parTraverse(2, function ($x) {
        return io(function () use ($x) {
            echo "Processing $x...\n";
            usleep(200_000); // 200ms

            return $x * 2;
        });
    });

echo "IO constructed (not executed yet)\n";
echo "Executing now...\n";

$start = microtime(true);
$result = $io->unsafeRunSync();
$duration = microtime(true) - $start;

echo "\nResult stream: " . implode(", ", $result->compile()->toArray()) . "\n";
echo sprintf("Execution time: %.2f seconds\n\n", $duration);

// ============================================================================
// Example 7: parEval for Side Effects
// ============================================================================
echo "7. parEval for Parallel Side Effects\n";
echo str_repeat("-", 70) . "\n";

$logFile = sys_get_temp_dir() . '/phunkie_concurrent_log.txt';
file_put_contents($logFile, ''); // Clear log

function logMessage(string $message): \Phunkie\Effect\IO\IO
{
    return io(function () use ($message) {
        global $logFile;
        usleep(100_000); // Simulate slow logging
        file_put_contents($logFile, "[" . microtime(true) . "] $message\n", FILE_APPEND);
    });
}

$messages = ["Message 1", "Message 2", "Message 3", "Message 4"];

$start = microtime(true);
Stream(...$messages)
    ->parEval(2, fn ($msg) => logMessage($msg))
    ->unsafeRunSync();
$duration = microtime(true) - $start;

$logContents = file_get_contents($logFile);
echo "Logged " . count($messages) . " messages in parallel\n";
echo sprintf("Time: %.2f seconds\n", $duration);
echo "Log contents:\n" . $logContents . "\n";

// ============================================================================
// Example 8: Comparing Execution Contexts
// ============================================================================
echo "8. Comparing Execution Contexts\n";
echo str_repeat("-", 70) . "\n";

// FiberExecutionContext (default - uses PHP Fibers)
$start = microtime(true);
$fiberResult = Stream(1, 2, 3, 4)
    ->parMap(2, function ($x) {
        usleep(100_000);

        return $x * 2;
    }, new FiberExecutionContext())
    ->compile()
    ->toArray();
$fiberTime = microtime(true) - $start;

echo "FiberExecutionContext:\n";
echo "  Result: " . implode(", ", $fiberResult) . "\n";
echo sprintf("  Time: %.2f seconds\n", $fiberTime);

// ParallelExecutionContext (uses ext-parallel if available)
if (class_exists('\parallel\Runtime')) {
    $start = microtime(true);
    $parallelResult = Stream(1, 2, 3, 4)
        ->parMap(2, function ($x) {
            usleep(100_000);

            return $x * 2;
        }, new ParallelExecutionContext())
        ->compile()
        ->toArray();
    $parallelTime = microtime(true) - $start;

    echo "\nParallelExecutionContext:\n";
    echo "  Result: " . implode(", ", $parallelResult) . "\n";
    echo sprintf("  Time: %.2f seconds\n", $parallelTime);
} else {
    echo "\nParallelExecutionContext: Not available (ext-parallel not installed)\n";
}
echo "\n";

// ============================================================================
// Example 9: Auto-Detection of CPU Cores
// ============================================================================
echo "9. Auto-Detection of CPU Cores\n";
echo str_repeat("-", 70) . "\n";

// When maxConcurrent = 0, it auto-detects CPU cores
$result = Stream(range(1, 20))
    ->flatMap(fn ($arr) => Stream(...$arr))
    ->parMap(0, fn ($x) => $x * 2) // 0 = auto-detect
    ->compile()
    ->toArray();

echo "Processed 20 elements with auto-detected concurrency\n";
echo "First 10 results: " . implode(", ", array_slice($result, 0, 10)) . "...\n";
echo "\n";

// ============================================================================
// Example 10: Automatic Fallback to Sequential
// ============================================================================
echo "10. Automatic Fallback to Sequential Execution\n";
echo str_repeat("-", 70) . "\n";

// Even if concurrent execution fails, operations continue sequentially
$result = Stream(1, 2, 3, 4, 5)
    ->parMap(2, fn ($x) => $x * 2)
    ->compile()
    ->toArray();

echo "Result: " . implode(", ", $result) . "\n";
echo "If concurrent execution fails, automatically falls back to sequential.\n";
echo "Check error_log for fallback messages.\n\n";

echo "=== All Examples Complete ===\n";
