# System Integration

This section provides recipes for integrating Phunkie Streams with system-level operations and PHP functions.

> **Note**: Phunkie Streams does not currently provide a `Process` class for executing system commands. The examples below show integration patterns using PHP's built-in functions and common extensions.

## Working with System Commands

### Simple Command Execution with Stream Processing

**Problem**: Execute system commands and process their output line by line.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\resource\bracket;
use function Phunkie\Effect\Functions\io\io;

// Execute command and get output
$executeCommand = function(string $command): IO {
    return bracket(
        // Acquire: open process pipe
        io(fn() => popen($command, 'r')),
        // Use: read output
        fn($handle) => io(function() use ($handle) {
            $lines = [];
            while (!feof($handle)) {
                $line = fgets($handle);
                if ($line !== false) {
                    $lines[] = trim($line);
                }
            }
            return $lines;
        }),
        // Release: close pipe
        fn($handle) => io(fn() => pclose($handle))
    );
};

// Process command output as a stream
$result = $executeCommand('ls -la')
    ->map(fn($lines) => Stream(...$lines))
    ->flatMap(fn($stream) => io(fn() =>
        $stream
            ->filter(fn($line) => !empty($line))
            ->filter(fn($line) => !str_starts_with($line, 'total'))
            ->toArray()
    ))
    ->unsafeRunSync();
```

**Discussion**: This recipe shows how to execute system commands safely using bracket for resource management and process the output as a stream.

### Processing Command Output with Error Handling

**Problem**: Execute a command and handle potential errors.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;

$executeCommand = function(string $command) {
    return bracket(
        io(fn() => popen($command, 'r')),
        fn($handle) => io(function() use ($handle) {
            if ($handle === false) {
                throw new \RuntimeException("Failed to execute command");
            }
            $lines = [];
            while (!feof($handle)) {
                $line = fgets($handle);
                if ($line !== false) {
                    $lines[] = trim($line);
                }
            }
            return $lines;
        }),
        fn($handle) => io(fn() => $handle !== false ? pclose($handle) : null)
    );
};

// With error handling
$result = $executeCommand('find /tmp -name "*.txt"')
    ->attempt()
    ->map(fn($validation) => $validation->getOrElse([]))
    ->map(fn($lines) => Stream(...$lines))
    ->flatMap(fn($stream) => io(fn() => $stream->take(10)->toArray()))
    ->unsafeRunSync();
```

**Discussion**: Using `attempt()` converts exceptions to Validation, allowing graceful error handling.

## PHP Extension Integration

### Working with Multibyte Strings (mbstring)

**Problem**: Process UTF-8 text files with multibyte string operations.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\IO\File\{readLines, writeLines};
use Phunkie\Streams\IO\File\Path;

// Process UTF-8 file
$processUtf8 = function(Path $input, Path $output) {
    return readLines($input)
        ->map(fn($lines) => array_map(
            fn($line) => mb_strtoupper($line, 'UTF-8'),
            $lines
        ))
        ->flatMap(fn($processed) => writeLines($output, $processed));
};

$processUtf8(
    new Path('utf8-input.txt'),
    new Path('utf8-output.txt')
)->unsafeRunSync();
```

**Discussion**: The `mbstring` extension provides functions for handling multibyte strings, essential for non-ASCII character sets.

### JSON Data Processing

**Problem**: Read JSON lines file, process data, and write results.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use Phunkie\Streams\IO\File\Path;
use function Phunkie\Streams\IO\File\writeFile;

// Process JSON lines file
Stream(new Path('data.jsonl'))
    ->map(fn($line) => json_decode($line, true))
    ->filter(fn($data) => $data !== null)
    ->filter(fn($data) => isset($data['active']) && $data['active'])
    ->map(fn($data) => [
        'id' => $data['id'],
        'name' => $data['name'],
        'score' => $data['score'] * 2
    ])
    ->map(fn($data) => json_encode($data))
    ->through(writeFile(new Path('processed.jsonl')));
```

**Discussion**: JSON processing is common for data interchange. Use `json_decode()` and `json_encode()` within stream transformations.

### Compression with zlib

**Problem**: Read a large file, compress content, and write to output.

**Solution**:
```php
<?php
use function Phunkie\Streams\IO\File\{readFileContents, writeFileContents};
use Phunkie\Streams\IO\File\Path;

// Compress file contents
$compressFile = function(Path $input, Path $output) {
    return readFileContents($input)
        ->map(fn($content) => gzcompress($content, 9))
        ->flatMap(fn($compressed) => writeFileContents($output, $compressed));
};

// Decompress file contents
$decompressFile = function(Path $input, Path $output) {
    return readFileContents($input)
        ->map(fn($content) => gzuncompress($content))
        ->flatMap(fn($decompressed) => writeFileContents($output, $decompressed));
};

$compressFile(
    new Path('large-file.txt'),
    new Path('large-file.txt.gz')
)->unsafeRunSync();

$decompressFile(
    new Path('large-file.txt.gz'),
    new Path('restored.txt')
)->unsafeRunSync();
```

**Discussion**: The `zlib` extension provides compression functions useful for reducing storage and bandwidth.

## Environment and System Information

### Reading Environment Variables

**Problem**: Configure stream processing based on environment variables.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Effect\Functions\io\io;

// Read config from environment
$getConfig = io(fn() => [
    'api_url' => getenv('API_URL') ?: 'https://api.example.com',
    'api_key' => getenv('API_KEY') ?: '',
    'batch_size' => (int)(getenv('BATCH_SIZE') ?: 100),
]);

// Use config in stream processing
$processData = $getConfig
    ->flatMap(fn($config) =>
        Network::httpGet($config['api_url'] . '/data', [
            "Authorization: Bearer {$config['api_key']}"
        ])
        ->map(fn($chunk) => json_decode($chunk, true))
        ->filter(fn($data) => $data !== null)
        ->compile->toArray()
    );

$result = $processData->unsafeRunSync();
```

**Discussion**: Environment variables provide configuration without hardcoding values.

### System Information Stream

**Problem**: Gather system information for monitoring.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Effect\Functions\io\io;

// Gather system metrics
$systemInfo = io(fn() => [
    'timestamp' => time(),
    'memory_usage' => memory_get_usage(true),
    'memory_peak' => memory_get_peak_usage(true),
    'php_version' => PHP_VERSION,
    'os' => PHP_OS,
]);

// Stream periodic system info (in a real scenario, you'd use a timer)
$metrics = Stream(
    $systemInfo,
    $systemInfo,
    $systemInfo
)->map(fn($io) => $io->unsafeRunSync())
 ->map(fn($info) => json_encode($info))
 ->toArray();
```

**Discussion**: Combine system functions with streams for monitoring and metrics collection.

## File System Operations

### Directory Traversal

**Problem**: Process all files in a directory matching a pattern.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\IO\File\readFileContents;
use Phunkie\Streams\IO\File\Path;

// Get all .txt files from directory
$files = array_filter(
    scandir('/path/to/directory'),
    fn($file) => str_ends_with($file, '.txt')
);

// Process each file
$results = Stream(...$files)
    ->map(fn($file) => new Path("/path/to/directory/$file"))
    ->map(fn($path) => readFileContents($path)
        ->map(fn($content) => [
            'file' => $path->toString(),
            'size' => strlen($content),
            'lines' => count(explode("\n", $content))
        ])
        ->unsafeRunSync()
    )
    ->toArray();
```

**Discussion**: Combine PHP's directory functions with stream processing for batch file operations.

### Temporary Files

**Problem**: Create temporary files for intermediate processing.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\IO\File\{writeFileContents, readFileContents, deleteFile};
use Phunkie\Streams\IO\File\Path;

$processWith TempFile = function(array $data) {
    $tempPath = new Path(tempnam(sys_get_temp_dir(), 'stream_'));

    return writeFileContents($tempPath, json_encode($data))
        ->flatMap(fn($_) => readFileContents($tempPath))
        ->map(fn($json) => json_decode($json, true))
        ->map(fn($decoded) => array_map(fn($item) => $item['value'] ?? 0, $decoded))
        ->flatMap(fn($result) => deleteFile($tempPath)->map(fn($_) => $result));
};

$result = $processWith TempFile([
    ['value' => 10],
    ['value' => 20],
    ['value' => 30]
])->unsafeRunSync();
// Result: [10, 20, 30]
```

**Discussion**: Use `tempnam()` with proper cleanup via `deleteFile()` for temporary file operations.

## Best Practices

1. **Resource Management**
   - Always use `bracket()` for system resources (pipes, file handles)
   - Clean up temporary files explicitly with `deleteFile()`
   - Let file I/O functions handle resources automatically

2. **Error Handling**
   - Use `attempt()` for operations that may fail
   - Provide sensible defaults with `getOrElse()`
   - Handle filesystem errors gracefully

3. **Performance**
   - Use appropriate buffer sizes for file operations
   - Process large files in chunks
   - Avoid loading entire files into memory when streaming is possible

4. **Security**
   - Validate file paths before operations
   - Sanitize command input if using `popen()` or similar
   - Be careful with environment variables and user input
   - Never execute unvalidated system commands

## Limitations

**Process Management**: Phunkie Streams does not currently provide dedicated process management abstractions. For complex process requirements:

- Use PHP's `proc_open()` for advanced process control
- Consider dedicated process libraries if needed
- Wrap process operations in IO and bracket for safety

**Concurrency**: Advanced concurrency features (parallel processing, backpressure) are planned for Phase 6 but not yet implemented.

## See Also

- [Resource Management](../resource-management.md) - bracket() vs __destruct() patterns
- [Error Handling](../error-handling.md) - Comprehensive error handling patterns
- [Advanced Topics](../advanced-topics.md) - Complex stream processing patterns
- [Resource Streams](../resource-streams.md) - File and network I/O
