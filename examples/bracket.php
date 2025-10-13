<?php

/**
 * Bracket Pattern Examples
 *
 * This file demonstrates the bracket pattern for safe resource management.
 * Bracket ensures resources are always properly released, even when errors occur.
 */

use function Phunkie\Effect\Functions\io\io;
use function Phunkie\Streams\Functions\resource\bracket;
use function Phunkie\Streams\IO\File\deleteFile;
use function Phunkie\Streams\IO\File\exists;

use Phunkie\Streams\IO\File\Path;

use function Phunkie\Streams\IO\File\readFileContents;
use function Phunkie\Streams\IO\File\readLines;
use function Phunkie\Streams\IO\File\writeFileContents;
use function Phunkie\Streams\IO\File\writeLines;

require_once dirname(__FILE__, 2) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/printLn.php';

echo "=== Bracket Pattern Examples ===\n\n";

// Example 1: Simple File Reading with Bracket
echo "1. Reading a file with bracket:\n";
$content = readFileContents(new Path(__FILE__))
    ->map(fn ($c) => substr($c, 0, 100) . "...")
    ->unsafeRunSync();
echo "   File content preview: " . substr($content, 0, 60) . "...\n\n";

// Example 2: Writing to a file with bracket
echo "2. Writing to a file with bracket:\n";
$testFile = new Path(sys_get_temp_dir() . '/bracket_test.txt');
$writeResult = writeFileContents($testFile, "Hello from bracket!\nThis is line 2.\n")
    ->unsafeRunSync();
echo "   Wrote $writeResult bytes to temporary file\n\n";

// Example 3: Reading what we just wrote
echo "3. Reading back what we wrote:\n";
$readBack = readFileContents($testFile)
    ->unsafeRunSync();
echo "   Read back: " . str_replace("\n", "\\n", $readBack) . "\n\n";

// Example 4: Working with lines
echo "4. Writing and reading lines:\n";
$lines = ['First line', 'Second line', 'Third line'];
$lineCount = writeLines($testFile, $lines)
    ->unsafeRunSync();
echo "   Wrote $lineCount lines\n";

$readLines = readLines($testFile)
    ->unsafeRunSync();
echo "   Read back " . count($readLines) . " lines:\n";
foreach ($readLines as $i => $line) {
    echo "   Line " . ($i + 1) . ": $line\n";
}
echo "\n";

// Example 5: Chaining file operations
echo "5. Chaining file operations with flatMap:\n";
$chainResult = writeFileContents($testFile, "Original content")
    ->flatMap(fn ($_) => readFileContents($testFile))
    ->map(fn ($content) => strtoupper($content))
    ->flatMap(fn ($upper) => writeFileContents($testFile, $upper))
    ->flatMap(fn ($_) => readFileContents($testFile))
    ->unsafeRunSync();
echo "   Final content: $chainResult\n\n";

// Example 6: Error handling with bracket and attempt
echo "6. Error handling with bracket:\n";
$nonExistentFile = new Path('/nonexistent/file.txt');
$attemptRead = readFileContents($nonExistentFile)
    ->attempt()
    ->unsafeRunSync();

// Pattern match on the result using getOrElse
$result = $attemptRead->getOrElse("Error: File not found");
echo "   Result: $result\n";
echo "\n";

// Example 7: Custom bracket usage
echo "7. Custom bracket example (manual resource management):\n";
$customBracket = bracket(
    // Acquire: Open file
    io(fn () => fopen($testFile->toString(), 'r')),
    // Use: Read first line
    fn ($handle) => io(function () use ($handle) {
        $firstLine = fgets($handle);

        return trim($firstLine);
    }),
    // Release: Always close file
    fn ($handle) => io(function () use ($handle) {
        fclose($handle);
        echo "   [Resource released: file handle closed]\n";
    })
);

$firstLine = $customBracket->unsafeRunSync();
echo "   First line: $firstLine\n\n";

// Example 8: Bracket with error handling
echo "8. Bracket ensures cleanup even on errors:\n";
$errorBracket = bracket(
    io(fn () => fopen($testFile->toString(), 'r')),
    fn ($handle) => io(function () use ($handle) {
        fgets($handle); // Read one line

        throw new \RuntimeException("Simulated error!");
    }),
    fn ($handle) => io(function () use ($handle) {
        fclose($handle);
        echo "   [Cleanup called even after error!]\n";
    })
);

try {
    $errorBracket->unsafeRunSync();
} catch (\RuntimeException $e) {
    echo "   Caught error: " . $e->getMessage() . "\n";
}
echo "\n";

// Example 9: File existence check and cleanup
echo "9. Checking file existence and cleanup:\n";
$fileExists = exists($testFile)->unsafeRunSync();
echo "   Test file exists: " . ($fileExists ? 'yes' : 'no') . "\n";

if ($fileExists) {
    $deleted = deleteFile($testFile)->unsafeRunSync();
    echo "   Test file deleted: " . ($deleted ? 'yes' : 'no') . "\n";
}
echo "\n";

// Example 10: Composing multiple bracket operations
echo "10. Composing multiple file operations:\n";
$tempFile1 = new Path(sys_get_temp_dir() . '/bracket_test1.txt');
$tempFile2 = new Path(sys_get_temp_dir() . '/bracket_test2.txt');

$composed = writeFileContents($tempFile1, "Content for file 1")
    ->flatMap(fn ($_) => writeFileContents($tempFile2, "Content for file 2"))
    ->flatMap(fn ($_) => io(fn () => [
        'file1' => readFileContents($tempFile1)->unsafeRunSync(),
        'file2' => readFileContents($tempFile2)->unsafeRunSync(),
    ]))
    ->flatMap(fn ($contents) => io(function () use ($tempFile1, $tempFile2, $contents) {
        deleteFile($tempFile1)->unsafeRunSync();
        deleteFile($tempFile2)->unsafeRunSync();

        return $contents;
    }))
    ->unsafeRunSync();

echo "   File 1: " . $composed['file1'] . "\n";
echo "   File 2: " . $composed['file2'] . "\n";
echo "   Both files created, read, and cleaned up!\n\n";

echo "=== All bracket examples completed! ===\n";
