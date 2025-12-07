<?php

/**
 * Stream Composition with flatMap Examples
 *
 * This file demonstrates monadic composition patterns using flatMap() from phunkie/effect IO.
 * flatMap allows you to sequence IO operations where each step depends on the result of the
 * previous step, enabling powerful and type-safe composition.
 */

use function Phunkie\Effect\Functions\io\io;

use Phunkie\Effect\IO\IO;

use function Phunkie\Streams\Functions\file\deleteFile;
use function Phunkie\Streams\Functions\file\exists;

use Phunkie\Streams\IO\File\Path;

use function Phunkie\Streams\Functions\file\readFileContents;
use function Phunkie\Streams\Functions\file\readLines;
use function Phunkie\Streams\Functions\file\writeFileContents;
use function Phunkie\Streams\Functions\file\writeLines;

require_once dirname(__FILE__, 2) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/printLn.php';

echo "=== Stream Composition with flatMap Examples ===\n\n";

// Example 1: Basic flatMap - sequencing dependent operations
echo "1. Basic flatMap - write then read:\n";
$tempFile = new Path(sys_get_temp_dir() . '/composition_basic.txt');

$result = writeFileContents($tempFile, "Hello, flatMap!")
    ->flatMap(fn ($bytesWritten) => readFileContents($tempFile))
    ->unsafeRunSync();

echo "   Result: $result\n";
deleteFile($tempFile)->unsafeRunSync();
echo "\n";

// Example 2: Chaining multiple flatMaps
echo "2. Chaining multiple dependent operations:\n";
$tempFile = new Path(sys_get_temp_dir() . '/composition_chain.txt');

$result = writeFileContents($tempFile, "original")
    ->flatMap(fn ($_) => readFileContents($tempFile))
    ->flatMap(fn ($content) => writeFileContents($tempFile, strtoupper($content)))
    ->flatMap(fn ($_) => readFileContents($tempFile))
    ->unsafeRunSync();

echo "   Final content: $result\n";
deleteFile($tempFile)->unsafeRunSync();
echo "\n";

// Example 3: flatMap vs map - understanding the difference
echo "3. Difference between map and flatMap:\n";
$tempFile = new Path(sys_get_temp_dir() . '/composition_diff.txt');

writeFileContents($tempFile, "test")->unsafeRunSync();

// map: transforms the value inside IO
$mapped = readFileContents($tempFile)
    ->map(fn ($content) => strtoupper($content))
    ->unsafeRunSync();
echo "   Using map: $mapped\n";

// flatMap: transforms and flattens nested IO
$flatMapped = readFileContents($tempFile)
    ->flatMap(fn ($content) => io(fn () => strtoupper($content)))
    ->unsafeRunSync();
echo "   Using flatMap: $flatMapped\n";

deleteFile($tempFile)->unsafeRunSync();
echo "\n";

// Example 4: Conditional composition
echo "4. Conditional operations with flatMap:\n";
$tempFile = new Path(sys_get_temp_dir() . '/composition_cond.txt');

$result = exists($tempFile)
    ->flatMap(function ($fileExists) use ($tempFile) {
        if ($fileExists) {
            return readFileContents($tempFile)
                ->map(fn ($content) => "Existing: $content");
        } else {
            return writeFileContents($tempFile, "New file")
                ->map(fn ($_) => "Created new file");
        }
    })
    ->unsafeRunSync();

echo "   First run: $result\n";

// Run again - file now exists
$result2 = exists($tempFile)
    ->flatMap(function ($fileExists) use ($tempFile) {
        if ($fileExists) {
            return readFileContents($tempFile)
                ->map(fn ($content) => "Existing: $content");
        } else {
            return writeFileContents($tempFile, "New file")
                ->map(fn ($_) => "Created new file");
        }
    })
    ->unsafeRunSync();

echo "   Second run: $result2\n";
deleteFile($tempFile)->unsafeRunSync();
echo "\n";

// Example 5: Building a pipeline
echo "5. Building a data processing pipeline:\n";
$inputFile = new Path(sys_get_temp_dir() . '/composition_input.txt');
$outputFile = new Path(sys_get_temp_dir() . '/composition_output.txt');

$pipeline = writeLines($inputFile, ["hello world", "functional programming", "phunkie streams"])
    ->flatMap(fn ($_) => readLines($inputFile))
    ->map(fn ($lines) => array_map('strtoupper', $lines))
    ->map(fn ($lines) => array_map(fn ($line) => ">> $line", $lines))
    ->flatMap(fn ($processed) => writeLines($outputFile, $processed))
    ->flatMap(fn ($_) => readLines($outputFile))
    ->unsafeRunSync();

echo "   Processed lines:\n";
foreach ($pipeline as $line) {
    echo "   $line\n";
}

deleteFile($inputFile)->unsafeRunSync();
deleteFile($outputFile)->unsafeRunSync();
echo "\n";

// Example 6: Error handling in composition
echo "6. Error handling within flatMap chains:\n";
$tempFile = new Path(sys_get_temp_dir() . '/composition_error.txt');

$result = writeFileContents($tempFile, "data")
    ->flatMap(fn ($_) => readFileContents($tempFile))
    ->flatMap(function ($content) use ($tempFile) {
        if (strlen($content) < 10) {
            return io(fn () => "Content too short: $content");
        }

        return writeFileContents($tempFile, $content . " - extended");
    })
    ->handleError(fn ($e) => "Error: " . $e->getMessage())
    ->unsafeRunSync();

echo "   Result: $result\n";
deleteFile($tempFile)->unsafeRunSync();
echo "\n";

// Example 7: Parallel operations (independent) vs sequential (flatMap)
echo "7. Understanding sequential composition:\n";
$file1 = new Path(sys_get_temp_dir() . '/comp_file1.txt');
$file2 = new Path(sys_get_temp_dir() . '/comp_file2.txt');

// Sequential with flatMap - second depends on first
$sequential = writeFileContents($file1, "File 1")
    ->flatMap(fn ($bytes1) => writeFileContents($file2, "File 2 (after file 1, $bytes1 bytes)"))
    ->flatMap(fn ($_) => io(fn () => [
        'file1' => readFileContents($file1)->unsafeRunSync(),
        'file2' => readFileContents($file2)->unsafeRunSync(),
    ]))
    ->unsafeRunSync();

echo "   File 1: {$sequential['file1']}\n";
echo "   File 2: {$sequential['file2']}\n";

deleteFile($file1)->unsafeRunSync();
deleteFile($file2)->unsafeRunSync();
echo "\n";

// Example 8: Composing with validation
echo "8. Composition with validation:\n";
$tempFile = new Path(sys_get_temp_dir() . '/composition_valid.txt');

function validateContent(string $content): IO
{
    return io(function () use ($content) {
        if (empty($content)) {
            throw new \InvalidArgumentException("Content cannot be empty");
        }
        if (strlen($content) > 100) {
            throw new \InvalidArgumentException("Content too long");
        }

        return $content;
    });
}

$result = writeFileContents($tempFile, "Valid content")
    ->flatMap(fn ($_) => readFileContents($tempFile))
    ->flatMap(fn ($content) => validateContent($content))
    ->map(fn ($validated) => "Validated: $validated")
    ->handleError(fn ($e) => "Validation failed: " . $e->getMessage())
    ->unsafeRunSync();

echo "   Result: $result\n";
deleteFile($tempFile)->unsafeRunSync();
echo "\n";

// Example 9: Nested flatMaps for complex workflows
echo "9. Complex workflow with nested operations:\n";
$sourceFile = new Path(sys_get_temp_dir() . '/source.txt');
$backupFile = new Path(sys_get_temp_dir() . '/backup.txt');
$processedFile = new Path(sys_get_temp_dir() . '/processed.txt');

$workflow = writeFileContents($sourceFile, "important data")
    ->flatMap(
        fn ($_) =>
        // Create backup
        readFileContents($sourceFile)
            ->flatMap(fn ($content) => writeFileContents($backupFile, $content))
    )
    ->flatMap(
        fn ($_) =>
        // Process original
        readFileContents($sourceFile)
            ->map(fn ($content) => "[PROCESSED] " . strtoupper($content))
            ->flatMap(fn ($processed) => writeFileContents($processedFile, $processed))
    )
    ->flatMap(
        fn ($_) =>
        // Verify all files
        io(fn () => [
            'source' => file_exists($sourceFile->toString()),
            'backup' => file_exists($backupFile->toString()),
            'processed' => file_exists($processedFile->toString()),
        ])
    )
    ->unsafeRunSync();

echo "   Source exists: " . ($workflow['source'] ? 'yes' : 'no') . "\n";
echo "   Backup exists: " . ($workflow['backup'] ? 'yes' : 'no') . "\n";
echo "   Processed exists: " . ($workflow['processed'] ? 'yes' : 'no') . "\n";

deleteFile($sourceFile)->unsafeRunSync();
deleteFile($backupFile)->unsafeRunSync();
deleteFile($processedFile)->unsafeRunSync();
echo "\n";

// Example 10: Reusable compositions
echo "10. Building reusable composition functions:\n";

function readOrCreate(Path $path, string $defaultContent): IO
{
    return exists($path)
        ->flatMap(function ($fileExists) use ($path, $defaultContent) {
            if ($fileExists) {
                return readFileContents($path);
            }

            return writeFileContents($path, $defaultContent)
                ->map(fn ($_) => $defaultContent);
        });
}

function updateFile(Path $path, callable $transform): IO
{
    return readFileContents($path)
        ->map($transform)
        ->flatMap(fn ($newContent) => writeFileContents($path, $newContent))
        ->map(fn ($_) => "Updated");
}

$tempFile = new Path(sys_get_temp_dir() . '/reusable.txt');

$result1 = readOrCreate($tempFile, "Default content")
    ->unsafeRunSync();
echo "   Read or create: $result1\n";

$result2 = updateFile($tempFile, fn ($content) => strtoupper($content))
    ->unsafeRunSync();
echo "   Update result: $result2\n";

$result3 = readOrCreate($tempFile, "Default content")
    ->unsafeRunSync();
echo "   Read again: $result3\n";

deleteFile($tempFile)->unsafeRunSync();
echo "\n";

// Example 11: Combining map and flatMap effectively
echo "11. Effective use of map and flatMap together:\n";
$tempFile = new Path(sys_get_temp_dir() . '/map_flatmap.txt');

$result = writeFileContents($tempFile, "raw data")
    ->flatMap(fn ($_) => readFileContents($tempFile))  // IO operation: flatMap
    ->map(fn ($content) => trim($content))             // Pure transform: map
    ->map(fn ($content) => strtoupper($content))       // Pure transform: map
    ->map(fn ($content) => "Processed: $content")      // Pure transform: map
    ->flatMap(fn ($processed) => writeFileContents($tempFile, $processed))  // IO operation: flatMap
    ->flatMap(fn ($_) => readFileContents($tempFile))  // IO operation: flatMap
    ->unsafeRunSync();

echo "   Final result: $result\n";
deleteFile($tempFile)->unsafeRunSync();
echo "\n";

// Example 12: Real-world scenario - config file management
echo "12. Real-world scenario - configuration management:\n";
$configFile = new Path(sys_get_temp_dir() . '/config.txt');

function loadConfig(Path $path): IO
{
    return exists($path)
        ->flatMap(function ($fileExists) use ($path) {
            if (!$fileExists) {
                $default = "app_name=MyApp\nversion=1.0.0";

                return writeFileContents($path, $default)
                    ->map(fn ($_) => $default);
            }

            return readFileContents($path);
        })
        ->map(function ($content) {
            $config = [];
            foreach (explode("\n", $content) as $line) {
                if (strpos($line, '=') !== false) {
                    [$key, $value] = explode('=', $line, 2);
                    $config[$key] = $value;
                }
            }

            return $config;
        });
}

function saveConfig(Path $path, array $config): IO
{
    $lines = array_map(fn ($k, $v) => "$k=$v", array_keys($config), array_values($config));
    $content = implode("\n", $lines);

    return writeFileContents($path, $content);
}

$result = loadConfig($configFile)
    ->flatMap(function ($config) use ($configFile) {
        // Update version
        $config['version'] = '2.0.0';
        $config['updated_at'] = date('Y-m-d H:i:s');

        return saveConfig($configFile, $config)
            ->map(fn ($_) => $config);
    })
    ->unsafeRunSync();

echo "   Config updated:\n";
foreach ($result as $key => $value) {
    echo "   $key = $value\n";
}

deleteFile($configFile)->unsafeRunSync();
echo "\n";

echo "=== All composition examples completed! ===\n";
