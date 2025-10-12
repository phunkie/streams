# Stream Composition with flatMap

This guide covers monadic composition patterns using `flatMap()` from phunkie/effect IO, enabling powerful and type-safe sequencing of effectful operations.

## Table of Contents

1. [Overview](#overview)
2. [Understanding flatMap](#understanding-flatmap)
3. [flatMap vs map](#flatmap-vs-map)
4. [Composition Patterns](#composition-patterns)
5. [Best Practices](#best-practices)
6. [Examples](#examples)

---

## Overview

**Monadic composition** is the foundation of functional programming with effects. In Phunkie Streams, the `flatMap()` method allows you to:

- **Sequence** dependent IO operations
- **Chain** multiple operations where each depends on the previous result
- **Compose** complex workflows from simple building blocks
- **Maintain type safety** throughout the pipeline

### Key Concept

`flatMap()` is used when an operation **returns another IO** that needs to be executed. It automatically "flattens" nested `IO<IO<A>>` into `IO<A>`.

---

## Understanding flatMap

### Signature

```php
IO<A> -> flatMap(A => IO<B>) -> IO<B>
```

### What Does flatMap Do?

1. Takes an `IO<A>` containing a value of type `A`
2. Applies a function `A => IO<B>` that produces another IO
3. **Flattens** the result from `IO<IO<B>>` to `IO<B>`
4. Returns the flattened `IO<B>`

### Basic Example

```php
use function Phunkie\Streams\IO\File\{writeFileContents, readFileContents};

$result = writeFileContents($path, "hello")
    ->flatMap(fn($bytesWritten) => readFileContents($path))
    ->unsafeRunSync();

// Result: "hello"
```

Here:
- `writeFileContents` returns `IO<int>` (bytes written)
- The lambda takes the `int` and returns `IO<string>` (read content)
- `flatMap` flattens `IO<IO<string>>` → `IO<string>`

---

## flatMap vs map

### When to Use map

Use `map` when you want to **transform a value** with a **pure function**:

```php
$result = readFileContents($path)
    ->map(fn($content) => strtoupper($content))  // Pure transformation
    ->unsafeRunSync();
```

**Signature**: `IO<A> -> map(A => B) -> IO<B>`

### When to Use flatMap

Use `flatMap` when your transformation **returns another IO**:

```php
$result = readFileContents($path)
    ->flatMap(fn($content) => writeFileContents($path2, $content))  // Returns IO
    ->unsafeRunSync();
```

**Signature**: `IO<A> -> flatMap(A => IO<B>) -> IO<B>`

### Comparison Table

| Operation | Function Type | Use Case | Example |
|-----------|--------------|----------|---------|
| `map` | `A => B` | Transform value | `map(fn($x) => strtoupper($x))` |
| `flatMap` | `A => IO<B>` | Chain IO operations | `flatMap(fn($x) => readFile($x))` |

### Visual Representation

```
map:     IO<A> --[A => B]--> IO<B>

flatMap: IO<A> --[A => IO<B>]--> IO<IO<B>> --[flatten]--> IO<B>
```

---

## Composition Patterns

### 1. Sequential Operations

Chain operations where each depends on the previous result.

```php
$result = writeFileContents($path, "original")
    ->flatMap(fn($_) => readFileContents($path))
    ->flatMap(fn($content) => writeFileContents($path, strtoupper($content)))
    ->flatMap(fn($_) => readFileContents($path))
    ->unsafeRunSync();

// Result: "ORIGINAL"
```

**Use when**: Operations must happen in a specific order.

---

### 2. Conditional Branching

Execute different IO operations based on conditions.

```php
$result = exists($path)
    ->flatMap(function($fileExists) use ($path) {
        if ($fileExists) {
            return readFileContents($path);
        } else {
            return writeFileContents($path, "default")
                ->map(fn($_) => "default");
        }
    })
    ->unsafeRunSync();
```

**Use when**: The next operation depends on a runtime condition.

---

### 3. Data Pipelines

Build multi-stage data transformation pipelines.

```php
$result = readLines($inputPath)
    ->map(fn($lines) => array_map('strtoupper', $lines))
    ->map(fn($lines) => array_filter($lines, fn($l) => !empty($l)))
    ->flatMap(fn($processed) => writeLines($outputPath, $processed))
    ->flatMap(fn($_) => readLines($outputPath))
    ->unsafeRunSync();
```

**Use when**: Processing data through multiple stages.

---

### 4. Error Handling in Chains

Combine composition with error handling.

```php
$result = readFileContents($path)
    ->flatMap(function($content) {
        if (strlen($content) < 10) {
            return io(fn() => throw new \RuntimeException("Too short"));
        }
        return io(fn() => $content);
    })
    ->handleError(fn($e) => "Error: " . $e->getMessage())
    ->unsafeRunSync();
```

**Use when**: Validation or error handling is needed mid-pipeline.

---

### 5. Reusable Composition Functions

Create composable building blocks.

```php
function readOrCreate(Path $path, string $default): IO {
    return exists($path)
        ->flatMap(function($fileExists) use ($path, $default) {
            if ($fileExists) {
                return readFileContents($path);
            }
            return writeFileContents($path, $default)
                ->map(fn($_) => $default);
        });
}

function updateFile(Path $path, callable $transform): IO {
    return readFileContents($path)
        ->map($transform)
        ->flatMap(fn($newContent) => writeFileContents($path, $newContent));
}

// Use them together
$result = readOrCreate($path, "initial")
    ->flatMap(fn($_) => updateFile($path, fn($c) => strtoupper($c)))
    ->unsafeRunSync();
```

**Use when**: Building a library of reusable operations.

---

### 6. Nested Compositions

Handle complex workflows with nested operations.

```php
$result = writeFileContents($source, "data")
    ->flatMap(fn($_) =>
        // Create backup
        readFileContents($source)
            ->flatMap(fn($content) => writeFileContents($backup, $content))
    )
    ->flatMap(fn($_) =>
        // Process original
        readFileContents($source)
            ->map(fn($content) => strtoupper($content))
            ->flatMap(fn($processed) => writeFileContents($processed, $processed))
    )
    ->unsafeRunSync();
```

**Use when**: Operations have sub-workflows.

---

### 7. Validation Pipelines

Compose operations with validation at each step.

```php
function validateNotEmpty(string $content): IO {
    return io(function() use ($content) {
        if (empty($content)) {
            throw new \InvalidArgumentException("Empty content");
        }
        return $content;
    });
}

function validateLength(string $content, int $max): IO {
    return io(function() use ($content, $max) {
        if (strlen($content) > $max) {
            throw new \InvalidArgumentException("Content too long");
        }
        return $content;
    });
}

$result = readFileContents($path)
    ->flatMap(fn($content) => validateNotEmpty($content))
    ->flatMap(fn($content) => validateLength($content, 100))
    ->flatMap(fn($validated) => writeFileContents($output, $validated))
    ->handleError(fn($e) => "Validation failed: " . $e->getMessage())
    ->unsafeRunSync();
```

**Use when**: Multiple validation steps are required.

---

### 8. Combining map and flatMap

Use the right tool for each step.

```php
$result = readFileContents($path)
    ->map(fn($content) => trim($content))           // Pure: map
    ->map(fn($content) => strtoupper($content))     // Pure: map
    ->flatMap(fn($upper) => writeFileContents($path, $upper))  // IO: flatMap
    ->flatMap(fn($_) => readFileContents($path))    // IO: flatMap
    ->unsafeRunSync();
```

**Rule of thumb**:
- Pure transformations? Use `map`
- Returns IO? Use `flatMap`

---

## Best Practices

### 1. Use map for Pure Transformations

```php
// Good ✓
readFileContents($path)
    ->map(fn($content) => strtoupper($content))

// Bad ✗ (unnecessary IO wrapping)
readFileContents($path)
    ->flatMap(fn($content) => io(fn() => strtoupper($content)))
```

### 2. Chain Related Operations

```php
// Good ✓ - Clear pipeline
readFileContents($path)
    ->flatMap(fn($content) => validateContent($content))
    ->flatMap(fn($valid) => processContent($valid))
    ->flatMap(fn($processed) => writeFileContents($output, $processed))

// Bad ✗ - Nested callbacks
readFileContents($path)
    ->flatMap(fn($content) =>
        validateContent($content)
            ->flatMap(fn($valid) =>
                processContent($valid)
                    ->flatMap(fn($processed) =>
                        writeFileContents($output, $processed)
                    )
            )
    )
```

### 3. Extract Reusable Functions

```php
// Good ✓
function safeReadFile(Path $path): IO {
    return readFileContents($path)
        ->handleError(fn($e) => "");
}

$result = safeReadFile($path1)
    ->flatMap(fn($_) => safeReadFile($path2))
    ->unsafeRunSync();

// Bad ✗ - Repetitive
$result = readFileContents($path1)
    ->handleError(fn($e) => "")
    ->flatMap(fn($_) => readFileContents($path2))
    ->handleError(fn($e) => "")
    ->unsafeRunSync();
```

### 4. Handle Errors at Appropriate Levels

```php
// Good ✓ - Handle at the end
$result = operation1()
    ->flatMap(fn($_) => operation2())
    ->flatMap(fn($_) => operation3())
    ->handleError(fn($e) => "Pipeline failed")
    ->unsafeRunSync();

// Also good ✓ - Handle specific operations
$result = operation1()
    ->handleError(fn($e) => "Op1 failed")
    ->flatMap(fn($_) => operation2())
    ->handleError(fn($e) => "Op2 failed")
    ->unsafeRunSync();
```

### 5. Use Type Hints for Clarity

```php
function processFile(Path $input, Path $output): IO {
    return readFileContents($input)
        ->map(fn(string $content): string => strtoupper($content))
        ->flatMap(fn(string $processed): IO => writeFileContents($output, $processed));
}
```

---

## Examples

### Complete File Processing Workflow

```php
function processFileWorkflow(Path $input, Path $output, Path $backup): IO {
    return readFileContents($input)
        // Validate
        ->flatMap(function($content) {
            if (empty($content)) {
                return io(fn() => throw new \RuntimeException("Empty file"));
            }
            return io(fn() => $content);
        })
        // Create backup
        ->flatMap(fn($content) =>
            writeFileContents($backup, $content)
                ->map(fn($_) => $content)
        )
        // Process
        ->map(fn($content) => strtoupper($content))
        ->map(fn($content) => trim($content))
        // Save
        ->flatMap(fn($processed) => writeFileContents($output, $processed))
        // Return summary
        ->flatMap(fn($bytesWritten) =>
            io(fn() => "Processed $bytesWritten bytes successfully")
        )
        ->handleError(fn($e) => "Error: " . $e->getMessage());
}

$result = processFileWorkflow($in, $out, $backup)->unsafeRunSync();
```

### Configuration Manager

```php
function loadConfig(Path $path): IO {
    return exists($path)
        ->flatMap(function($fileExists) use ($path) {
            if (!$fileExists) {
                return writeFileContents($path, "key=value")
                    ->map(fn($_) => ['key' => 'value']);
            }
            return readFileContents($path)
                ->map(function($content) {
                    $config = [];
                    foreach (explode("\n", $content) as $line) {
                        if (strpos($line, '=') !== false) {
                            [$k, $v] = explode('=', $line, 2);
                            $config[$k] = $v;
                        }
                    }
                    return $config;
                });
        });
}

function saveConfig(Path $path, array $config): IO {
    $lines = array_map(fn($k, $v) => "$k=$v", array_keys($config), $config);
    $content = implode("\n", $lines);
    return writeFileContents($path, $content);
}

function updateConfig(Path $path, string $key, string $value): IO {
    return loadConfig($path)
        ->map(function($config) use ($key, $value) {
            $config[$key] = $value;
            return $config;
        })
        ->flatMap(fn($updated) => saveConfig($path, $updated));
}

// Usage
$result = updateConfig($configPath, 'version', '2.0.0')
    ->flatMap(fn($_) => loadConfig($configPath))
    ->unsafeRunSync();
```

### Batch File Processor

```php
function processBatch(array $paths, callable $transform): IO {
    $processFile = function(Path $path) use ($transform): IO {
        return readFileContents($path)
            ->map($transform)
            ->flatMap(fn($transformed) => writeFileContents($path, $transformed))
            ->attempt()
            ->map(function($validation) use ($path) {
                return [
                    'path' => $path->toString(),
                    'success' => $validation->getOrElse(null) !== null,
                ];
            });
    };

    return io(function() use ($paths, $processFile) {
        $results = [];
        foreach ($paths as $path) {
            $results[] = $processFile($path)->unsafeRunSync();
        }
        return $results;
    });
}

// Usage
$results = processBatch(
    [$file1, $file2, $file3],
    fn($content) => strtoupper($content)
)->unsafeRunSync();
```

---

## Summary

### Key Takeaways

1. **`flatMap` is for sequencing IO operations** where each depends on the previous result
2. **`map` is for pure transformations** that don't require IO
3. **Compose operations** into reusable functions for clarity
4. **Handle errors** at appropriate levels in your pipeline
5. **Keep chains flat** - avoid deeply nested flatMaps

### Decision Tree

```
Does my function return IO?
├─ Yes → Use flatMap
└─ No  → Use map
```

### Pattern Summary

| Pattern | When to Use |
|---------|-------------|
| Sequential Operations | Operations must run in order |
| Conditional Branching | Next step depends on a condition |
| Data Pipelines | Multi-stage transformations |
| Reusable Functions | Building composable operations |
| Validation Chains | Multiple validation steps |
| Error Handling | Recovering from failures |

For more examples, see:
- `examples/composition.php` - 12 comprehensive examples
- `examples/bracket.php` - Composition with resource management
- `tests/Feature/Streams/CompositionSpec.php` - Test suite

---

**Next**: Learn about [Core Stream Operations](./stream-operations.md)
