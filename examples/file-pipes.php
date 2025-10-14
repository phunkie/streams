<?php

/**
 * File I/O Pipe Examples
 *
 * This file demonstrates using the writeFile() pipe function for stream-based file I/O.
 * The writeFile() pipe allows you to write stream elements directly to files.
 */

use function Phunkie\Streams\IO\File\deleteFile;

use Phunkie\Streams\IO\File\Path;

use function Phunkie\Streams\IO\File\readLines;
use function Phunkie\Streams\IO\File\writeFile;

use Phunkie\Streams\Type\Stream;

require_once dirname(__FILE__, 2) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/printLn.php';

echo "=== File I/O Pipe Examples ===\n\n";

// Helper function to create temp files
$tempFile = fn ($name) => new Path(sys_get_temp_dir() . "/file_pipe_{$name}_" . uniqid() . ".txt");

// Example 1: Basic writeFile usage
echo "1. Writing a stream to a file:\n";
$file1 = $tempFile('basic');
Stream('Line 1', 'Line 2', 'Line 3')
    ->through(writeFile($file1));

$content = readLines($file1)->unsafeRunSync();
echo "   Written lines: " . json_encode($content) . "\n";
deleteFile($file1)->unsafeRunSync();
echo "\n";

// Example 2: Writing numbers to file
echo "2. Writing numbers to a file:\n";
$file2 = $tempFile('numbers');
Stream(1, 2, 3, 4, 5)
    ->through(writeFile($file2));

$content = readLines($file2)->unsafeRunSync();
echo "   Written content: " . json_encode($content) . "\n";
deleteFile($file2)->unsafeRunSync();
echo "\n";

// Example 3: Writing transformed data
echo "3. Writing transformed data:\n";
$file3 = $tempFile('transformed');
Stream(1, 2, 3, 4, 5)
    ->map(fn ($x) => $x * 10)
    ->map(fn ($x) => "Number: $x")
    ->through(writeFile($file3));

$content = readLines($file3)->unsafeRunSync();
echo "   Transformed output:\n";
foreach ($content as $line) {
    echo "   - $line\n";
}
deleteFile($file3)->unsafeRunSync();
echo "\n";

// Example 4: Filtering before writing
echo "4. Filtering data before writing:\n";
$file4 = $tempFile('filtered');
Stream(...[1, 2, 3, 4, 5, 6, 7, 8, 9, 10])
    ->filter(fn ($x) => $x % 2 === 0)
    ->map(fn ($x) => "Even: $x")
    ->through(writeFile($file4));

$content = readLines($file4)->unsafeRunSync();
echo "   Filtered output: " . json_encode($content) . "\n";
deleteFile($file4)->unsafeRunSync();
echo "\n";

// Example 5: Using through() to compose pipelines
echo "5. Composing pipelines with through():\n";
$file5 = $tempFile('pipeline');

$processNumbers = fn (Stream $s) => $s
    ->map(fn ($x) => $x * 2)
    ->filter(fn ($x) => $x > 5)
    ->map(fn ($x) => "Processed: $x");

Stream(...[1, 2, 3, 4, 5, 6])
    ->through($processNumbers)
    ->through(writeFile($file5));

$content = readLines($file5)->unsafeRunSync();
echo "   Pipeline output:\n";
foreach ($content as $line) {
    echo "   - $line\n";
}
deleteFile($file5)->unsafeRunSync();
echo "\n";

// Example 6: Writing text lines
echo "6. Writing text lines:\n";
$file6 = $tempFile('text');
$lines = [
    "This is a story about streams.",
    "Streams can be transformed.",
    "And written to files easily!",
];

Stream(...$lines)
    ->through(writeFile($file6));

$content = readLines($file6)->unsafeRunSync();
echo "   File contains " . count($content) . " lines\n";
deleteFile($file6)->unsafeRunSync();
echo "\n";

// Example 7: Writing CSV-like data
echo "7. Writing CSV-like data:\n";
$file7 = $tempFile('csv');

$data = [
    ['Name', 'Age', 'City'],
    ['Alice', 30, 'NYC'],
    ['Bob', 25, 'LA'],
    ['Charlie', 35, 'Chicago'],
];

Stream(...$data)
    ->map(fn ($row) => implode(',', $row))
    ->through(writeFile($file7));

$content = readLines($file7)->unsafeRunSync();
echo "   CSV output:\n";
foreach ($content as $line) {
    echo "   $line\n";
}
deleteFile($file7)->unsafeRunSync();
echo "\n";

// Example 8: Writing JSON lines (JSONL format)
echo "8. Writing JSON lines:\n";
$file8 = $tempFile('jsonl');

$records = [
    ['id' => 1, 'name' => 'Product A', 'price' => 10.99],
    ['id' => 2, 'name' => 'Product B', 'price' => 25.50],
    ['id' => 3, 'name' => 'Product C', 'price' => 5.75],
];

Stream(...$records)
    ->map(fn ($record) => json_encode($record))
    ->through(writeFile($file8));

$content = readLines($file8)->unsafeRunSync();
echo "   JSONL output:\n";
foreach ($content as $line) {
    echo "   $line\n";
}
deleteFile($file8)->unsafeRunSync();
echo "\n";

// Example 9: Writing formatted log entries
echo "9. Writing formatted log entries:\n";
$file9 = $tempFile('log');

$events = [
    ['level' => 'INFO', 'message' => 'Application started'],
    ['level' => 'WARN', 'message' => 'Low memory warning'],
    ['level' => 'ERROR', 'message' => 'Connection failed'],
];

Stream(...$events)
    ->map(fn ($event) => sprintf("[%s] %s", $event['level'], $event['message']))
    ->through(writeFile($file9));

$content = readLines($file9)->unsafeRunSync();
echo "   Log entries:\n";
foreach ($content as $line) {
    echo "   $line\n";
}
deleteFile($file9)->unsafeRunSync();
echo "\n";

// Example 10: Empty stream writes empty file
echo "10. Writing an empty stream:\n";
$file10 = $tempFile('empty');

Stream()
    ->through(writeFile($file10));

$content = readLines($file10)->unsafeRunSync();
echo "   Empty file has " . count($content) . " lines\n";
deleteFile($file10)->unsafeRunSync();
echo "\n";

// Example 11: Complex data processing pipeline
echo "11. Complex data processing pipeline:\n";
$file11 = $tempFile('complex');

$rawData = range(1, 20);

$processAndFormat = fn (Stream $s) => $s
    ->dropWhile(fn ($x) => $x < 5)      // Skip values less than 5
    ->takeWhile(fn ($x) => $x <= 15)    // Take values up to 15
    ->filter(fn ($x) => $x % 2 === 0)   // Only even numbers
    ->map(fn ($x) => $x * 10)           // Scale by 10
    ->map(fn ($x) => "Value: $x");      // Format as string

Stream(...$rawData)
    ->through($processAndFormat)
    ->through(writeFile($file11));

$content = readLines($file11)->unsafeRunSync();
echo "   Processed results:\n";
foreach ($content as $line) {
    echo "   - $line\n";
}
deleteFile($file11)->unsafeRunSync();
echo "\n";

// Example 12: Writing report with chunks
echo "12. Writing report with chunked data:\n";
$file12 = $tempFile('report');

$numbers = range(1, 12);

Stream(...$numbers)
    ->chunk(3)
    ->map(fn ($chunk) => sprintf(
        "Batch: [%s] Sum: %d",
        implode(', ', $chunk),
        array_sum($chunk)
    ))
    ->through(writeFile($file12));

$content = readLines($file12)->unsafeRunSync();
echo "   Report:\n";
foreach ($content as $line) {
    echo "   $line\n";
}
deleteFile($file12)->unsafeRunSync();
echo "\n";

echo "=== All file pipe examples completed! ===\n";
