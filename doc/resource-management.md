# Resource Management in Phunkie Streams

This guide explains how Phunkie Streams manages resources safely and efficiently, and when to use different resource management patterns.

## Overview

Phunkie Streams provides robust resource management through two complementary approaches:

1. **Automatic cleanup via `__destruct()`** - PHP's garbage collector automatically closes resources
2. **Explicit cleanup via `bracket()`** - Guaranteed finalization even in the presence of errors

## PHP's Resource Management Model

Unlike the JVM (which fs2 targets), PHP has **deterministic reference counting**:

- Resources are freed immediately when the last reference is dropped
- `__destruct()` is called as soon as an object's reference count reaches zero
- No unpredictable garbage collection delays
- Simpler mental model for resource lifecycle

This means in PHP, we can rely on `__destruct()` for most resource cleanup, reserving `bracket()` for cases requiring explicit control.

## The Resource Interface Pattern

All Phunkie Streams resources implement the `Resource` interface and follow this pattern:

```php
class SocketRead implements Resource
{
    private $socket;

    public function __construct(private SocketAddress $address) {
        // Constructor does NOT open the resource
    }

    public function __destruct()
    {
        // Destructor ensures cleanup
        if ($this->isOpen()) {
            $this->close();
        }
    }

    public function pull($bytes) {
        if (!$this->isOpen()) {
            $this->connect(); // Lazy connection
        }
        return $this->read($bytes);
    }

    private function close(): void {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }
    }
}
```

### Key Design Principles:

1. **Lazy acquisition** - Resources opened on first use, not in constructor
2. **Encapsulated lifecycle** - connect(), isOpen(), close() are private
3. **Guaranteed cleanup** - __destruct() always calls close()
4. **Idempotent close** - safe to call close() multiple times

## When to Use bracket()

Use `bracket()` when you need **explicit control** over resource lifecycle:

### Use bracket() for:

1. **Operations requiring immediate cleanup**
   ```php
   use function Phunkie\Streams\Functions\resource\bracket;

   bracket(
       io(fn() => fopen('file.txt', 'r')),      // Acquire
       fn($handle) => io(fn() => fread($handle, 1024)),  // Use
       fn($handle) => io(fn() => fclose($handle))        // Release
   )->unsafeRunSync();
   ```

2. **Coordinating multiple resources**
   ```php
   bracket(
       io(fn() => [fopen('in.txt', 'r'), fopen('out.txt', 'w')]),
       fn($handles) => io(function() use ($handles) {
           [$in, $out] = $handles;
           while (!feof($in)) {
               fwrite($out, fread($in, 4096));
           }
       }),
       fn($handles) => io(function() use ($handles) {
           fclose($handles[0]);
           fclose($handles[1]);
       })
   )->unsafeRunSync();
   ```

3. **Resources that must be closed in specific order**

4. **Resources exposed to user code** (where GC timing is uncertain)

### Current bracket() Usage in Phunkie Streams:

- **File I/O functions**: `readFileContents()`, `writeFileContents()`, `readLines()`, `writeLines()`
- **Raw socket creation**: `socket()` function
- These use bracket because they work with raw PHP resources (not Resource objects)

## When to Use __destruct()

Use `__destruct()` for **Resource objects** that manage their own lifecycle:

### Use __destruct() for:

1. **Stream-based resources**
   ```php
   // HttpRequest automatically cleans up when stream ends
   Network::httpGet('https://api.example.com/data')
       ->map(fn($chunk) => processChunk($chunk))
       ->compile->toArray();
   // HttpRequest's __destruct() called when stream is consumed
   ```

2. **Resources with single-use lifecycle**
   ```php
   // SocketRead connects, reads, disconnects - all managed internally
   Network::client(new SocketAddress('localhost', 8080))
       ->take(10)
       ->compile->toArray();
   // SocketRead's __destruct() called after take(10) completes
   ```

3. **Resources where timing doesn't matter** (as long as cleanup happens eventually)

### Current __destruct() Usage in Phunkie Streams:

- **HttpRequest** - HTTP client resource (src/IO/Network/HttpRequest.php:28-33)
- **SocketRead** - TCP client resource (src/IO/Network/SocketRead.php:23-28)
- **SocketServer** - TCP server resource (src/IO/Network/SocketServer.php:27-32)
- **Read** - File read resource (src/IO/Read.php:15-20)

## Decision Guide

Use this flowchart to decide which pattern to use:

```
┌─────────────────────────────────────┐
│ Do you need to expose raw resource  │
│ handles to user code?               │
└─────────────┬───────────────────────┘
              │
         Yes  │  No
              ▼
    ┌─────────────────────┐
    │ Use bracket()       │
    │ (explicit control)  │
    └─────────────────────┘
              │
              No
              ▼
┌─────────────────────────────────────┐
│ Does cleanup order matter across    │
│ multiple resources?                 │
└─────────────┬───────────────────────┘
              │
         Yes  │  No
              ▼
    ┌─────────────────────┐
    │ Use bracket()       │
    │ (coordinated)       │
    └─────────────────────┘
              │
              No
              ▼
┌─────────────────────────────────────┐
│ Is this a Resource object with      │
│ encapsulated lifecycle?             │
└─────────────┬───────────────────────┘
              │
         Yes  │  No
              ▼
    ┌─────────────────────┐
    │ Use __destruct()    │
    │ (automatic)         │
    └─────────────────────┘
```

## Examples

### Example 1: File I/O (bracket)

File operations use bracket because they expose raw handles:

```php
use function Phunkie\Streams\Functions\file\readFileContents;

// bracket ensures file is closed even if processing throws
$content = readFileContents(new Path('data.txt'))
    ->map(fn($text) => strtoupper($text))
    ->unsafeRunSync();
```

### Example 2: HTTP Requests (__destruct)

HTTP requests use __destruct because HttpRequest encapsulates lifecycle:

```php
use Phunkie\Streams\Network;

// HttpRequest's __destruct() automatically closes connection
$data = Network::httpGet('https://api.example.com/users')
    ->map(fn($chunk) => json_decode($chunk, true))
    ->filter(fn($data) => $data !== null)
    ->compile->toArray();
```

### Example 3: Socket Connections (__destruct)

Socket operations use __destruct because SocketRead manages state:

```php
use Phunkie\Streams\{Network, IO\Network\SocketAddress};

// SocketRead's __destruct() closes socket when done
$messages = Network::client(new SocketAddress('localhost', 8080))
    ->take(5)
    ->map(fn($msg) => trim($msg))
    ->compile->toArray();
```

### Example 4: Multiple Files (bracket)

When coordinating multiple files, use bracket:

```php
use function Phunkie\Streams\Functions\resource\bracket;

bracket(
    io(fn() => [
        fopen('source.txt', 'r'),
        fopen('dest.txt', 'w'),
        fopen('log.txt', 'a')
    ]),
    fn($handles) => io(function() use ($handles) {
        [$src, $dst, $log] = $handles;
        while (($line = fgets($src)) !== false) {
            fwrite($dst, strtoupper($line));
            fwrite($log, "Processed: " . trim($line) . "\n");
        }
    }),
    fn($handles) => io(function() use ($handles) {
        foreach ($handles as $handle) {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }
    })
)->unsafeRunSync();
```

## Error Handling

Both patterns handle errors safely:

### bracket() with Errors

```php
use function Phunkie\Streams\Functions\file\readFileContents;

$result = readFileContents(new Path('/nonexistent.txt'))
    ->attempt()
    ->unsafeRunSync();

// $result is Validation<Error, string>
$content = $result->getOrElse("default content");
```

### __destruct() with Errors

```php
use Phunkie\Streams\Network;

try {
    $data = Network::httpGet('https://invalid-domain.example.com')
        ->compile->toArray();
} catch (\RuntimeException $e) {
    // HttpRequest's __destruct() still called, connection cleaned up
    echo "Error: " . $e->getMessage();
}
```

## Testing Resource Cleanup

You can verify resources are properly cleaned up:

```php
// Track open file descriptors before
$before = count(get_resources());

Network::httpGet('https://httpbin.org/get')
    ->compile->toArray();

// Force garbage collection
gc_collect_cycles();

$after = count(get_resources());

assert($before === $after, "Resource leak detected!");
```

## Best Practices

1. **Prefer __destruct() for Resource objects** - Simpler, automatic, idiomatic PHP
2. **Use bracket() for raw resources** - When exposing file handles, sockets, etc.
3. **Never manually call __destruct()** - Let PHP's GC handle it
4. **Make close() idempotent** - Safe to call multiple times
5. **Acquire lazily** - Don't open resources in constructor
6. **Test cleanup** - Verify no leaks in error scenarios

## Common Pitfalls

### ❌ Don't: Store Resource references globally

```php
// BAD: Global reference prevents __destruct()
$GLOBALS['socket'] = Network::client($addr);
// __destruct() won't be called until script ends
```

### ✅ Do: Use local scopes

```php
// GOOD: Local scope, __destruct() called when function returns
function processData() {
    $socket = Network::client($addr);
    return $socket->take(10)->compile->toArray();
} // __destruct() called here
```

### ❌ Don't: Mix patterns unnecessarily

```php
// BAD: Using bracket with Resource object (unnecessary)
bracket(
    io(fn() => new HttpRequest($url)),
    fn($req) => io(fn() => $req->pull(4096)),
    fn($req) => io(fn() => $req->close())
);
```

### ✅ Do: Trust __destruct()

```php
// GOOD: HttpRequest handles its own lifecycle
Network::httpGet($url)->compile->toArray();
```

## Summary

| Pattern | When to Use | Example |
|---------|------------|---------|
| `bracket()` | Raw resources, explicit control, coordination | File I/O functions, socket() |
| `__destruct()` | Resource objects, encapsulated lifecycle | HttpRequest, SocketRead, SocketServer |

Phunkie Streams' resource management leverages PHP's deterministic GC model for safe, automatic cleanup while providing bracket() for cases requiring explicit control. This hybrid approach gives you the best of both worlds: simplicity where possible, control where needed.
