# Error Handling in Phunkie Streams

This guide covers error handling patterns using `attempt()` and `handleError()` from phunkie/effect IO.

## Table of Contents

1. [Overview](#overview)
2. [The attempt() Method](#the-attempt-method)
3. [The handleError() Method](#the-handleerror-method)
4. [Error Recovery Strategies](#error-recovery-strategies)
5. [Best Practices](#best-practices)
6. [Examples](#examples)

---

## Overview

Phunkie Streams leverages the IO monad from phunkie/effect, which provides two primary methods for error handling:

- **`attempt()`**: Captures errors into a `Validation` type, allowing you to handle success/failure as data
- **`handleError()`**: Provides a recovery function that runs when an error occurs

Both methods ensure that your error handling is **explicit**, **composable**, and **type-safe**.

---

## The attempt() Method

### What is attempt()?

`attempt()` transforms an `IO<A>` into an `IO<Validation<A>>`, capturing any errors that occur during execution into a Validation type.

### Signature

```php
IO<A> -> attempt() -> IO<Validation<A>>
```

### Basic Usage

```php
use function Phunkie\Streams\IO\File\readFileContents;

$result = readFileContents(new Path('/path/to/file.txt'))
    ->attempt()
    ->unsafeRunSync();

// Extract value with fallback
$content = $result->getOrElse("default content");
```

### When to Use attempt()

- When you want to **inspect** the error without immediately handling it
- When you need to **defer** error handling to later in the pipeline
- When you want to **transform** errors into values
- When building **validation pipelines** that accumulate errors

### Examples

#### Pattern Matching on Validation

```php
$validation = readFileContents($path)
    ->attempt()
    ->unsafeRunSync();

$content = $validation->getOrElse("File not found");
```

#### Transforming Errors

```php
$result = readFileContents($path)
    ->attempt()
    ->map(fn($validation) => $validation->getOrElse([]))
    ->unsafeRunSync();
```

#### Preserving Success/Failure Information

```php
$validation = writeFileContents($path, $content)
    ->attempt()
    ->unsafeRunSync();

if ($validation->getOrElse(null) !== null) {
    echo "Success!";
} else {
    echo "Failed!";
}
```

---

## The handleError() Method

### What is handleError()?

`handleError()` provides a recovery function that is called when an error occurs, allowing you to transform the error into a successful result.

### Signature

```php
IO<A> -> handleError(Throwable => A) -> IO<A>
```

### Basic Usage

```php
$content = readFileContents($path)
    ->handleError(fn($error) => "Error: " . $error->getMessage())
    ->unsafeRunSync();
```

### When to Use handleError()

- When you want to **recover** from errors immediately
- When you need to provide **fallback values**
- When errors should be **transparent** to downstream code
- When implementing **retry logic** or **fallback chains**

### Examples

#### Simple Recovery

```php
$content = readFileContents($path)
    ->handleError(fn($e) => "Default content")
    ->unsafeRunSync();
```

#### Contextual Error Messages

```php
$content = readFileContents($path)
    ->handleError(fn($e) => "Failed to read {$path->toString()}: {$e->getMessage()}")
    ->unsafeRunSync();
```

#### IO Operations in Error Handlers

```php
$content = readFileContents($primaryPath)
    ->handleError(function($e) use ($fallbackPath) {
        // Fallback to alternative file
        return readFileContents($fallbackPath)
            ->unsafeRunSync();
    })
    ->unsafeRunSync();
```

#### Type-Specific Error Handling

```php
$result = someOperation()
    ->handleError(function($error) {
        if ($error instanceof InvalidArgumentException) {
            return "Invalid argument provided";
        }
        if ($error instanceof IOException) {
            return "I/O error occurred";
        }
        return "Unknown error";
    })
    ->unsafeRunSync();
```

---

## Error Recovery Strategies

### 1. Provide Default Values

The simplest strategy: return a sensible default when an error occurs.

```php
$lines = readLines($path)
    ->handleError(fn($e) => [])  // Empty array as default
    ->unsafeRunSync();
```

**Use when**: The operation is optional or has a natural default value.

---

### 2. Fallback Chain (Retry with Alternatives)

Try multiple sources in order until one succeeds.

```php
$content = readFileContents($primaryPath)
    ->handleError(fn($e) => readFileContents($secondaryPath)->unsafeRunSync())
    ->handleError(fn($e) => readFileContents($tertiaryPath)->unsafeRunSync())
    ->handleError(fn($e) => "All sources failed")
    ->unsafeRunSync();
```

**Use when**: You have multiple data sources or backup files.

---

### 3. Transform Error to Value

Convert errors into meaningful data that downstream code can work with.

```php
$status = writeFileContents($path, $content)
    ->attempt()
    ->map(function($validation) {
        return $validation->getOrElse(null) !== null
            ? ['success' => true, 'bytes' => $validation->getOrElse(0)]
            : ['success' => false, 'error' => 'Write failed'];
    })
    ->unsafeRunSync();
```

**Use when**: Callers need to know both success and failure details.

---

### 4. Log and Continue

Handle the error by logging it, but continue with a fallback.

```php
$content = readFileContents($path)
    ->handleError(function($error) use ($logger) {
        $logger->error("Failed to read file", ['error' => $error->getMessage()]);
        return ""; // Continue with empty content
    })
    ->unsafeRunSync();
```

**Use when**: Errors should be recorded but not block execution.

---

### 5. Validate Before Acting

Check preconditions and only proceed if they're met.

```php
$result = exists($path)
    ->flatMap(function($fileExists) use ($path) {
        if (!$fileExists) {
            return io(fn() => throw new RuntimeException("File does not exist"));
        }
        return readFileContents($path);
    })
    ->handleError(fn($e) => "Error: " . $e->getMessage())
    ->unsafeRunSync();
```

**Use when**: You need explicit validation before operations.

---

### 6. Accumulate Errors

Use `attempt()` to collect errors without stopping execution.

```php
$results = [];
foreach ($paths as $path) {
    $validation = readFileContents($path)
        ->attempt()
        ->unsafeRunSync();

    $results[] = [
        'path' => $path->toString(),
        'content' => $validation->getOrElse(null),
        'error' => $validation->getOrElse(null) === null
    ];
}
```

**Use when**: You need to process multiple items and collect all results/errors.

---

### 7. Composable Error Handlers

Create reusable error handling functions.

```php
function safeReadFile(Path $path): IO {
    return readFileContents($path)
        ->handleError(fn($e) => "");
}

function safeReadLines(Path $path): IO {
    return readLines($path)
        ->handleError(fn($e) => []);
}

// Use anywhere
$content = safeReadFile($path)->unsafeRunSync();
$lines = safeReadLines($path)->unsafeRunSync();
```

**Use when**: You have common error handling patterns across your codebase.

---

### 8. Conditional Recovery

Different recovery strategies based on error type or context.

```php
$content = readFileContents($path)
    ->handleError(function($error) use ($path) {
        if ($error instanceof FileNotFoundException) {
            return "File not found: {$path->toString()}";
        }
        if ($error instanceof PermissionDeniedException) {
            return "Permission denied: {$path->toString()}";
        }
        return "Unknown error occurred";
    })
    ->unsafeRunSync();
```

**Use when**: Different error types require different handling.

---

## Best Practices

### 1. Use attempt() for Inspection, handleError() for Recovery

```php
// Inspect error ✓
$validation = operation()->attempt()->unsafeRunSync();
if ($validation->getOrElse(null) === null) {
    // Handle failure case
}

// Immediate recovery ✓
$result = operation()
    ->handleError(fn($e) => fallback())
    ->unsafeRunSync();
```

---

### 2. Chain Error Handlers

```php
$result = readFileContents($path)
    ->handleError(fn($e) => tryAlternative())
    ->handleError(fn($e) => "Final fallback")
    ->unsafeRunSync();
```

Each `handleError()` only runs if the previous operation failed.

---

### 3. Don't Swallow Errors Silently

```php
// Bad ✗
$content = readFileContents($path)
    ->handleError(fn($e) => "")
    ->unsafeRunSync();

// Good ✓
$content = readFileContents($path)
    ->handleError(function($e) use ($logger) {
        $logger->warning("Failed to read file", ['error' => $e->getMessage()]);
        return "";
    })
    ->unsafeRunSync();
```

---

### 4. Use Type-Safe Fallbacks

```php
// Ensure fallback has the same type as success case
$lines = readLines($path)
    ->handleError(fn($e) => [])  // Array, same as success
    ->unsafeRunSync();
```

---

### 5. Compose Error Handlers

```php
function withLogging(IO $io, LoggerInterface $logger): IO {
    return $io->handleError(function($e) use ($logger) {
        $logger->error($e->getMessage());
        throw $e; // Re-throw to propagate
    });
}

$result = withLogging(
    readFileContents($path),
    $logger
)->handleError(fn($e) => "fallback")
  ->unsafeRunSync();
```

---

## Examples

### Complete File Processing Pipeline

```php
use function Phunkie\Streams\IO\File\{readFileContents, writeFileContents, exists};

function processFile(Path $inputPath, Path $outputPath): IO {
    return exists($inputPath)
        ->flatMap(function($fileExists) use ($inputPath, $outputPath) {
            if (!$fileExists) {
                return io(fn() => "Input file does not exist");
            }

            return readFileContents($inputPath)
                ->map(fn($content) => strtoupper($content))
                ->flatMap(fn($processed) => writeFileContents($outputPath, $processed))
                ->map(fn($bytesWritten) => "Success: wrote $bytesWritten bytes")
                ->handleError(fn($e) => "Error: " . $e->getMessage());
        });
}

$result = processFile($input, $output)->unsafeRunSync();
echo $result;
```

---

### Batch Processing with Error Collection

```php
function processBatch(array $paths): array {
    $results = [];

    foreach ($paths as $path) {
        $validation = readFileContents($path)
            ->map(fn($content) => ['success' => true, 'content' => $content])
            ->attempt()
            ->map(fn($v) => $v->getOrElse(['success' => false, 'error' => 'Failed']))
            ->unsafeRunSync();

        $results[$path->toString()] = $validation;
    }

    return $results;
}
```

---

### Safe File Operations Helper

```php
class SafeFileOps {
    public static function readOrEmpty(Path $path): IO {
        return readFileContents($path)
            ->handleError(fn($e) => "");
    }

    public static function readLinesOrEmpty(Path $path): IO {
        return readLines($path)
            ->handleError(fn($e) => []);
    }

    public static function writeOrFail(Path $path, string $content): IO {
        return writeFileContents($path, $content)
            ->map(fn($bytes) => ['success' => true, 'bytes' => $bytes])
            ->handleError(fn($e) => ['success' => false, 'error' => $e->getMessage()]);
    }
}
```

---

## Summary

| Method | Returns | Use Case |
|--------|---------|----------|
| `attempt()` | `IO<Validation<A>>` | Inspect errors, defer handling |
| `handleError()` | `IO<A>` | Immediate recovery, fallbacks |

**Key Principles**:
- Errors are explicit and handled at the IO level
- Recovery is composable and type-safe
- Choose the right method based on your needs:
  - Need to inspect? Use `attempt()`
  - Need to recover? Use `handleError()`
  - Need both? Chain them!

For more examples, see:
- `examples/error-handling.php` - 12 comprehensive examples
- `examples/bracket.php` - Error handling with resource management
- `tests/Feature/Streams/ErrorHandlingSpec.php` - Test suite

---

**Next**: Learn about [Stream Composition with flatMap](./composition.md)
