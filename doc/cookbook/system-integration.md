# System Integration

This section provides recipes for integrating Phunkie Streams with system-level operations and PHP extensions.

## Process Management

### Running System Commands

**Problem**: Execute system commands and process their output.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\resource\bracket;

$output = Stream(new Process("ls -la"))
    ->through(bracket())
    ->map(fn($line) => trim($line))
    ->filter(fn($line) => !empty($line))
    ->compile()
    ->toList()
    ->unsafeRunSync();
```

**Discussion**: This recipe shows how to execute system commands and process their output using streams. The `bracket` pattern ensures proper process cleanup.

### Process Piping

**Problem**: Pipe output between multiple processes.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\resource\bracket;

$result = Stream(new Process("cat large-file.txt"))
    ->through(bracket())
    ->through(new Process("grep 'pattern'"))
    ->through(bracket())
    ->through(new Process("wc -l"))
    ->through(bracket())
    ->compile()
    ->toList()
    ->unsafeRunSync();
```

**Discussion**: Process piping allows you to chain multiple commands together, processing data through a pipeline.

## Signal Handling

### Handling System Signals

**Problem**: Handle system signals in a stream-based application.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\resource\bracket;

$signalHandler = new SignalHandler();
$signalHandler->register(SIGINT, function() {
    echo "Received SIGINT, shutting down...\n";
    exit(0);
});

$server = Stream(new TcpServer("localhost", 8080))
    ->through(bracket())
    ->map(fn($client) => Stream($client)
        ->through(bracket())
        ->map(fn($data) => processData($data))
        ->compile()
        ->drain
    )
    ->compile()
    ->drain;
```

**Discussion**: Signal handling allows your application to respond to system signals, such as SIGINT (Ctrl+C).

## System Calls

### Making System Calls

**Problem**: Make system calls from your application.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\resource\bracket;

$result = Stream(new SystemCall("uname -a"))
    ->through(bracket())
    ->map(fn($output) => trim($output))
    ->compile()
    ->toList()
    ->unsafeRunSync();
```

**Discussion**: System calls allow you to interact with the operating system directly.

## Extension Integration

### mbstring Integration

**Problem**: Process multibyte strings in a stream.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\resource\bracket;

$result = Stream(new Path("utf8-file.txt"))
    ->through(bracket())
    ->through(mbstring())
    ->map(fn($line) => mb_strtoupper($line, 'UTF-8'))
    ->compile()
    ->toList()
    ->unsafeRunSync();
```

**Discussion**: The `mbstring` extension provides functions for handling multibyte strings, which is essential for working with non-ASCII character sets.

### JSON Integration

**Problem**: Process JSON data in a stream.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\resource\bracket;

$result = Stream(new Path("data.json"))
    ->through(bracket())
    ->through(json())
    ->map(fn($data) => $data['key'])
    ->compile()
    ->toList()
    ->unsafeRunSync();
```

**Discussion**: The `json` extension provides functions for encoding and decoding JSON data, which is commonly used for data interchange.

### pcntl Integration

**Problem**: Use process control functions in a stream.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\resource\bracket;

$pid = pcntl_fork();
if ($pid == -1) {
    die('Could not fork');
} else if ($pid) {
    // Parent process
    $result = Stream(new Process("child-process"))
        ->through(bracket())
        ->compile()
        ->toList()
        ->unsafeRunSync();
} else {
    // Child process
    $result = Stream(new Process("parent-process"))
        ->through(bracket())
        ->compile()
        ->toList()
        ->unsafeRunSync();
}
```

**Discussion**: The `pcntl` extension provides functions for process control, allowing you to create and manage child processes.

### posix Integration

**Problem**: Use POSIX functions in a stream.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\resource\bracket;

$result = Stream(new PosixCall("getpid"))
    ->through(bracket())
    ->map(fn($pid) => "Process ID: $pid")
    ->compile()
    ->toList()
    ->unsafeRunSync();
```

**Discussion**: The `posix` extension provides functions for interacting with the POSIX API, which is useful for system-level operations.

### zlib Integration

**Problem**: Compress and decompress data in a stream.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\resource\bracket;

$compressed = Stream(new Path("large-file.txt"))
    ->through(bracket())
    ->through(zlib())
    ->compile()
    ->toList()
    ->unsafeRunSync();

$decompressed = Stream($compressed)
    ->through(zlib())
    ->compile()
    ->toList()
    ->unsafeRunSync();
```

**Discussion**: The `zlib` extension provides functions for compressing and decompressing data, which is useful for reducing storage and bandwidth usage.

## Blocker

### Using Blocker for Synchronous Operations

**Problem**: Execute synchronous operations in a stream without blocking the entire stream.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\resource\bracket;
use function Phunkie\Streams\Functions\blocker\block;

$result = Stream(new Path("data.txt"))
    ->through(bracket())
    ->through(block(fn($data) => expensiveOperation($data)))
    ->compile()
    ->toList()
    ->unsafeRunSync();
```

**Discussion**: The `block` function allows you to execute synchronous operations within a stream without blocking the entire stream. This is useful for operations that are inherently synchronous but need to be integrated into a streaming pipeline.

### Blocker with Timeout

**Problem**: Execute synchronous operations with a timeout.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\resource\bracket;
use function Phunkie\Streams\Functions\blocker\block;

$result = Stream(new Path("data.txt"))
    ->through(bracket())
    ->through(block(fn($data) => expensiveOperation($data), 5000)) // 5 second timeout
    ->compile()
    ->toList()
    ->unsafeRunSync();
```

**Discussion**: The `block` function can be configured with a timeout to prevent synchronous operations from running indefinitely.

### Blocker with Error Handling

**Problem**: Handle errors in synchronous operations within a stream.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\resource\bracket;
use function Phunkie\Streams\Functions\blocker\block;

$result = Stream(new Path("data.txt"))
    ->through(bracket())
    ->through(block(
        fn($data) => expensiveOperation($data),
        5000,
        fn($error) => handleError($error)
    ))
    ->compile()
    ->toList()
    ->unsafeRunSync();
```

**Discussion**: The `block` function can be configured with an error handler to manage errors that occur during synchronous operations.

## Best Practices

1. **Resource Management**
   - Always use `bracket` or `Scope` for system operations
   - Implement proper process cleanup
   - Handle system errors

2. **Error Handling**
   - Use `Validation` for system operations
   - Handle system errors
   - Implement retry mechanisms

3. **Performance**
   - Use appropriate buffer sizes
   - Implement backpressure mechanisms
   - Consider using process pooling

4. **Security**
   - Validate system input
   - Implement proper authentication
   - Use secure system calls

## See Also

- [Resource Management](../resource-management.md)
- [Error Handling](../error-handling.md)
- [Advanced Topics](../advanced-topics.md) 