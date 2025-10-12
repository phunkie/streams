<?php

/**
 * Stream Operations Examples
 *
 * This file demonstrates core stream operations: through(), takeWhile(), dropWhile(), and chunk().
 * These operations enable powerful stream transformations and composability.
 */

use Phunkie\Streams\Type\Stream;

require_once dirname(__FILE__, 2) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/printLn.php';

echo "=== Stream Operations Examples ===\n\n";

// Example 1: through() - Pipe operator
echo "1. Using through() to apply transformations:\n";
$uppercase = fn(Stream $s) => $s->map(fn($x) => strtoupper($x));

$result = Stream(...['hello', 'world', 'php'])
    ->through($uppercase)
    ->toArray();

echo "   Input: ['hello', 'world', 'php']\n";
echo "   Output: " . json_encode($result) . "\n\n";

// Example 2: Chaining through()
echo "2. Chaining multiple through() operations:\n";
$double = fn(Stream $s) => $s->map(fn($x) => $x * 2);
$addTen = fn(Stream $s) => $s->map(fn($x) => $x + 10);

$result = Stream(...[1, 2, 3, 4, 5])
    ->through($double)
    ->through($addTen)
    ->toArray();

echo "   Input: [1, 2, 3, 4, 5]\n";
echo "   After double: [2, 4, 6, 8, 10]\n";
echo "   After +10: " . json_encode($result) . "\n\n";

// Example 3: Complex pipeline with through()
echo "3. Complex pipeline composition:\n";
$pipeline = fn(Stream $s) => $s
    ->map(fn($x) => $x * 2)
    ->filter(fn($x) => $x > 5)
    ->map(fn($x) => "[$x]");

$result = Stream(...[1, 2, 3, 4, 5])
    ->through($pipeline)
    ->toArray();

echo "   Input: [1, 2, 3, 4, 5]\n";
echo "   Output: " . json_encode(array_values($result)) . "\n\n";

// Example 4: takeWhile() - Take elements while predicate is true
echo "4. Using takeWhile():\n";
$result = Stream(...[1, 2, 3, 4, 5, 1, 2, 3])
    ->takeWhile(fn($x) => $x < 4)
    ->toArray();

echo "   Input: [1, 2, 3, 4, 5, 1, 2, 3]\n";
echo "   takeWhile(x < 4): " . json_encode($result) . "\n";
echo "   Stops at first element >= 4\n\n";

// Example 5: takeWhile with strings
echo "5. takeWhile() with string validation:\n";
$result = Stream(...['apple', 'banana', 'cherry', '123', 'date'])
    ->takeWhile(fn($x) => ctype_alpha($x))
    ->toArray();

echo "   Input: ['apple', 'banana', 'cherry', '123', 'date']\n";
echo "   takeWhile(alphabetic): " . json_encode($result) . "\n";
echo "   Stops at first non-alphabetic element\n\n";

// Example 6: dropWhile() - Drop elements while predicate is true
echo "6. Using dropWhile():\n";
$result = Stream(...[1, 2, 3, 4, 5, 1, 2, 3])
    ->dropWhile(fn($x) => $x < 4)
    ->toArray();

echo "   Input: [1, 2, 3, 4, 5, 1, 2, 3]\n";
echo "   dropWhile(x < 4): " . json_encode($result) . "\n";
echo "   Includes all elements after first >= 4\n\n";

// Example 7: dropWhile with strings
echo "7. dropWhile() to skip headers:\n";
$result = Stream(...['#', '#', '# Header', 'Data 1', 'Data 2', 'Data 3'])
    ->dropWhile(fn($x) => str_starts_with($x, '#'))
    ->toArray();

echo "   Input: ['#', '#', '# Header', 'Data 1', 'Data 2', 'Data 3']\n";
echo "   dropWhile(starts with #): " . json_encode($result) . "\n\n";

// Example 8: Combining takeWhile and dropWhile
echo "8. Combining takeWhile and dropWhile:\n";
$result = Stream(...[1, 2, 3, 4, 5, 6, 7, 8, 9, 10])
    ->dropWhile(fn($x) => $x < 3)  // Skip first 2
    ->takeWhile(fn($x) => $x < 8)  // Take until 8
    ->toArray();

echo "   Input: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]\n";
echo "   dropWhile(x < 3) then takeWhile(x < 8): " . json_encode($result) . "\n";
echo "   Result: values from 3 to 7\n\n";

// Example 9: chunk() - Process in fixed-size groups
echo "9. Using chunk() to batch elements:\n";
$result = Stream(...[1, 2, 3, 4, 5, 6, 7, 8])
    ->chunk(3)
    ->toArray();

echo "   Input: [1, 2, 3, 4, 5, 6, 7, 8]\n";
echo "   chunk(3): \n";
foreach ($result as $i => $chunk) {
    echo "     Chunk " . ($i + 1) . ": " . json_encode($chunk) . "\n";
}
echo "\n";

// Example 10: chunk() for batch processing
echo "10. Batch processing with chunk():\n";
$numbers = range(1, 12);
$result = Stream(...$numbers)
    ->chunk(4)
    ->map(fn($chunk) => array_sum($chunk))
    ->toArray();

echo "   Input: " . json_encode($numbers) . "\n";
echo "   chunk(4) then sum each: " . json_encode($result) . "\n";
echo "   (Sums: 10, 26, 42)\n\n";

// Example 11: Real-world example - Data processing pipeline
echo "11. Real-world data processing pipeline:\n";

// Simulated data stream
$data = [
    0, 0, 0,           // Initial noise
    10, 15, 20, 25,    // Valid data
    30, 35, 40,        // More data
    999, 999,          // End markers
    50, 60             // After markers (ignored)
];

$processData = fn(Stream $s) => $s
    ->dropWhile(fn($x) => $x === 0)     // Skip initial zeros
    ->takeWhile(fn($x) => $x < 100)     // Stop at markers
    ->filter(fn($x) => $x % 5 === 0)    // Only multiples of 5
    ->map(fn($x) => $x * 2);            // Double them

$result = Stream(...$data)
    ->through($processData)
    ->toArray();

echo "   Raw data: " . json_encode($data) . "\n";
echo "   Processed: " . json_encode(array_values($result)) . "\n";
echo "   Pipeline: dropWhile(0) → takeWhile(<100) → filter(%5) → double\n\n";

// Example 12: Text processing with operations
echo "12. Text processing example:\n";
$lines = [
    "# Comment",
    "# Another comment",
    "START",
    "line1: data",
    "line2: more data",
    "line3: even more",
    "END",
    "ignored line"
];

$processText = fn(Stream $s) => $s
    ->dropWhile(fn($line) => str_starts_with($line, '#'))
    ->dropWhile(fn($line) => $line === 'START')  // Drop START marker
    ->takeWhile(fn($line) => $line !== 'END')    // Stop at END marker
    ->filter(fn($line) => str_contains($line, ':'))
    ->map(fn($line) => explode(': ', $line)[1]);

$result = Stream(...$lines)
    ->through($processText)
    ->toArray();

echo "   Extracted data: " . json_encode(array_values($result)) . "\n\n";

// Example 13: Chunked stream processing
echo "13. Processing data in chunks:\n";
$items = range(1, 10);

$result = Stream(...$items)
    ->chunk(3)
    ->map(function($chunk) {
        $sum = array_sum($chunk);
        $avg = $sum / count($chunk);
        return ['sum' => $sum, 'avg' => $avg, 'size' => count($chunk)];
    })
    ->toArray();

echo "   Input: " . json_encode($items) . "\n";
echo "   Chunked statistics:\n";
foreach ($result as $i => $stats) {
    echo "     Chunk " . ($i + 1) . ": " . json_encode($stats) . "\n";
}
echo "\n";

// Example 14: Reusable transformation pipes
echo "14. Building reusable transformation pipes:\n";

// Define reusable pipes
$removeNegatives = fn(Stream $s) => $s->filter(fn($x) => $x >= 0);
$doublePositives = fn(Stream $s) => $s->map(fn($x) => $x * 2);
$formatNumbers = fn(Stream $s) => $s->map(fn($x) => "[$x]");

$result = Stream(...[-5, -3, 0, 2, 4, -1, 6, 8])
    ->through($removeNegatives)
    ->through($doublePositives)
    ->through($formatNumbers)
    ->toArray();

echo "   Input: [-5, -3, 0, 2, 4, -1, 6, 8]\n";
echo "   After pipeline: " . json_encode(array_values($result)) . "\n\n";

// Example 15: Combining all operations
echo "15. Complex example combining all operations:\n";

$dataset = range(1, 20);

$complexPipeline = fn(Stream $s) => $s
    ->dropWhile(fn($x) => $x < 5)      // Skip first 4
    ->takeWhile(fn($x) => $x <= 15)    // Take up to 15
    ->filter(fn($x) => $x % 2 === 0)   // Only evens
    ->chunk(2)                          // Group in pairs
    ->map(fn($pair) => [
        'pair' => $pair,
        'sum' => array_sum($pair),
        'product' => array_product($pair)
    ]);

$result = Stream(...$dataset)
    ->through($complexPipeline)
    ->toArray();

echo "   Input: " . json_encode($dataset) . "\n";
echo "   Complex pipeline results:\n";
foreach ($result as $i => $stats) {
    echo "     Group " . ($i + 1) . ": " . json_encode($stats) . "\n";
}
echo "\n";

echo "=== All stream operations examples completed! ===\n";
