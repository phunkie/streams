<?php

/**
 * Error Handling Examples
 *
 * This file demonstrates error handling patterns using attempt() and handleError()
 * from phunkie/effect IO. These patterns enable functional error recovery and
 * composition while maintaining type safety.
 */

use Phunkie\Streams\IO\File\Path;
use function Phunkie\Streams\IO\File\exists;
use function Phunkie\Streams\IO\File\readFileContents;
use function Phunkie\Streams\IO\File\writeFileContents;
use function Phunkie\Streams\IO\File\readLines;
use function Phunkie\Effect\Functions\io\io;

require_once dirname(__FILE__, 2) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/printLn.php';

echo "=== Error Handling Examples ===\n\n";

// Example 1: Basic attempt() usage
echo "1. Using attempt() to catch errors:\n";
$nonExistentFile = new Path('/nonexistent/file.txt');

$attemptResult = readFileContents($nonExistentFile)
    ->attempt()
    ->unsafeRunSync();

// Validation provides getOrElse for safe value extraction
$content = $attemptResult->getOrElse("File not found - using fallback");
echo "   Result: $content\n\n";

// Example 2: Pattern matching on Validation
echo "2. Pattern matching with Validation:\n";
$result = readFileContents($nonExistentFile)
    ->attempt()
    ->unsafeRunSync();

// Check if it's a success or failure
$isSuccess = $result->getOrElse(null) !== null;
echo "   Is success: " . ($isSuccess ? "yes" : "no") . "\n";
echo "   Value or default: " . $result->getOrElse("default value") . "\n\n";

// Example 3: handleError() for recovery
echo "3. Using handleError() to recover from errors:\n";
$recovered = readFileContents($nonExistentFile)
    ->handleError(fn($error) => "Error recovered: " . $error->getMessage())
    ->unsafeRunSync();

echo "   Recovered value: $recovered\n\n";

// Example 4: handleError with fallback file
echo "4. Fallback to alternative file:\n";
$primaryFile = new Path('/nonexistent/primary.txt');
$fallbackFile = new Path(__FILE__); // This file exists

$withFallback = readFileContents($primaryFile)
    ->handleError(function($error) use ($fallbackFile) {
        return readFileContents($fallbackFile)
            ->map(fn($content) => substr($content, 0, 100) . "...")
            ->unsafeRunSync();
    })
    ->unsafeRunSync();

echo "   Got content from fallback: " . substr($withFallback, 0, 50) . "...\n\n";

// Example 5: Chaining with error handling
echo "5. Chaining operations with error handling:\n";
$tempFile = new Path(sys_get_temp_dir() . '/error_test.txt');

$chainedResult = writeFileContents($tempFile, "Original content")
    ->flatMap(fn($_) => readFileContents($tempFile))
    ->map(fn($content) => strtoupper($content))
    ->handleError(fn($e) => "ERROR: " . $e->getMessage())
    ->unsafeRunSync();

echo "   Result: $chainedResult\n";

// Clean up
if (file_exists($tempFile->toString())) {
    unlink($tempFile->toString());
}
echo "\n";

// Example 6: attempt() in a chain
echo "6. Using attempt() in a pipeline:\n";
$pipelineResult = writeFileContents($tempFile, "Test content")
    ->flatMap(fn($_) => readFileContents($tempFile))
    ->attempt()
    ->map(function($validation) {
        // Process the Validation
        return $validation->getOrElse("Failed to read");
    })
    ->unsafeRunSync();

echo "   Pipeline result: $pipelineResult\n";

// Clean up
if (file_exists($tempFile->toString())) {
    unlink($tempFile->toString());
}
echo "\n";

// Example 7: Multiple error handling strategies
echo "7. Multiple error handling strategies:\n";

// Strategy 1: Provide default value
$strategy1 = readFileContents($nonExistentFile)
    ->attempt()
    ->map(fn($v) => $v->getOrElse("DEFAULT"))
    ->unsafeRunSync();
echo "   Strategy 1 (default): $strategy1\n";

// Strategy 2: Recover with computation
$strategy2 = readFileContents($nonExistentFile)
    ->handleError(fn($e) => "Computed fallback: " . date('Y-m-d H:i:s'))
    ->unsafeRunSync();
echo "   Strategy 2 (computed): $strategy2\n";

// Strategy 3: Transform error to success
$strategy3 = readFileContents($nonExistentFile)
    ->handleError(fn($e) => "Error was: " . get_class($e))
    ->unsafeRunSync();
echo "   Strategy 3 (transform): $strategy3\n\n";

// Example 8: Validating operations
echo "8. Validation with file operations:\n";
$testFile = new Path(sys_get_temp_dir() . '/validation_test.txt');

$validationResult = writeFileContents($testFile, "Valid content")
    ->flatMap(fn($_) => exists($testFile))
    ->flatMap(function($fileExists) use ($testFile) {
        if (!$fileExists) {
            return io(fn() => throw new \RuntimeException("File should exist!"));
        }
        return readFileContents($testFile);
    })
    ->handleError(fn($e) => "Validation failed: " . $e->getMessage())
    ->unsafeRunSync();

echo "   Validation result: $validationResult\n";

// Clean up
if (file_exists($testFile->toString())) {
    unlink($testFile->toString());
}
echo "\n";

// Example 9: Composing error handlers
echo "9. Composing multiple error handlers:\n";

function safeReadFile(Path $path): string {
    return readFileContents($path)
        ->handleError(function($e) use ($path) {
            // First level: Try to provide context
            if ($e instanceof \RuntimeException) {
                return "RuntimeException reading {$path->toString()}";
            }
            return "Unknown error reading file";
        })
        ->unsafeRunSync();
}

$composedResult = safeReadFile($nonExistentFile);
echo "   Composed result: $composedResult\n\n";

// Example 10: Error handling with resource cleanup
echo "10. Error handling ensures resource cleanup:\n";
$cleanupFile = new Path(sys_get_temp_dir() . '/cleanup_test.txt');

// Create a file that we'll try to read with an error in processing
writeFileContents($cleanupFile, "Content to process")
    ->unsafeRunSync();

$processWithError = readFileContents($cleanupFile)
    ->map(function($content) {
        // Simulate an error during processing
        if (strlen($content) > 0) {
            throw new \RuntimeException("Processing error!");
        }
        return $content;
    })
    ->handleError(fn($e) => "Caught error: " . $e->getMessage())
    ->unsafeRunSync();

echo "   Processed with error handling: $processWithError\n";
echo "   File still exists: " . (file_exists($cleanupFile->toString()) ? "yes" : "no") . "\n";

// Clean up
if (file_exists($cleanupFile->toString())) {
    unlink($cleanupFile->toString());
}
echo "\n";

// Example 11: Error handling with readLines
echo "11. Error handling with readLines:\n";
$linesResult = readLines($nonExistentFile)
    ->attempt()
    ->map(fn($v) => $v->getOrElse([]))
    ->unsafeRunSync();

echo "   Lines read (or empty): " . count($linesResult) . " lines\n\n";

// Example 12: Practical error handling pattern
echo "12. Practical error handling pattern:\n";

function readFileOrCreate(Path $path, string $defaultContent): string {
    return exists($path)
        ->flatMap(function($fileExists) use ($path, $defaultContent) {
            if ($fileExists) {
                return readFileContents($path);
            }
            // File doesn't exist, create it with default content
            return writeFileContents($path, $defaultContent)
                ->map(fn($_) => $defaultContent);
        })
        ->handleError(fn($e) => "Error: " . $e->getMessage())
        ->unsafeRunSync();
}

$practicalFile = new Path(sys_get_temp_dir() . '/practical_test.txt');
$content1 = readFileOrCreate($practicalFile, "Initial content");
echo "   First read (created): $content1\n";

$content2 = readFileOrCreate($practicalFile, "Initial content");
echo "   Second read (existing): $content2\n";

// Clean up
if (file_exists($practicalFile->toString())) {
    unlink($practicalFile->toString());
}
echo "\n";

echo "=== All error handling examples completed! ===\n";
