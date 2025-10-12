# File Processes

This section provides recipes for working with files using Phunkie Streams.

## Reading Files

### Basic File Reading

**Problem**: Read a file line by line.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\resource\bracket;

$lines = Stream(new Path("file.txt"))
    ->through(bracket())
    ->compile()
    ->toList()
    ->unsafeRunSync();
```

**Discussion**: This recipe uses the `bracket` pattern to ensure the file is properly closed after reading. The stream will emit each line of the file as a separate value.

### Reading Large Files

**Problem**: Process a large file without loading it entirely into memory.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\resource\bracket;

$processed = Stream(new Path("large-file.txt"))
    ->through(bracket())
    ->chunk(1000)  // Process 1000 lines at a time
    ->map(fn($chunk) => processChunk($chunk))
    ->compile()
    ->drain;
```

**Discussion**: By using `chunk()`, we can process the file in manageable pieces, preventing memory issues with large files.

## Writing Files

### Basic File Writing

**Problem**: Write data to a file.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\resource\bracket;

$data = ["line1", "line2", "line3"];
Stream($data)
    ->through(bracket(new Path("output.txt", "w")))
    ->map(fn($line) => $line . PHP_EOL)
    ->compile()
    ->drain;
```

**Discussion**: The `bracket` pattern ensures the file is properly closed after writing. We use `map` to add line endings to each line.

### Appending to Files

**Problem**: Append data to an existing file.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\resource\bracket;

$newData = ["line4", "line5"];
Stream($newData)
    ->through(bracket(new Path("existing.txt", "a")))
    ->map(fn($line) => $line . PHP_EOL)
    ->compile()
    ->drain;
```

**Discussion**: Using mode "a" opens the file for appending, preserving existing content.

## File Transformations

### CSV to JSON Conversion

**Problem**: Convert a CSV file to JSON format.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\resource\bracket;

$json = Stream(new Path("data.csv"))
    ->through(bracket())
    ->map(fn($line) => str_getcsv($line))
    ->map(fn($row) => [
        "id" => $row[0],
        "name" => $row[1],
        "value" => $row[2]
    ])
    ->compile()
    ->toList()
    ->unsafeRunSync();

Stream($json)
    ->through(bracket(new Path("output.json", "w")))
    ->map(fn($data) => json_encode($data, JSON_PRETTY_PRINT))
    ->compile()
    ->drain;
```

**Discussion**: This recipe demonstrates how to transform data between formats using streams. We first read the CSV, transform each row into a structured array, then write it as JSON.

### File Compression

**Problem**: Compress a file using gzip.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\resource\bracket;

Stream(new Path("large-file.txt"))
    ->through(bracket())
    ->through(bracket(new Path("compressed.gz", "w")))
    ->through(gzip())
    ->compile()
    ->drain;
```

**Discussion**: The `gzip()` function is a stream transformer that compresses the data as it flows through the stream.

## Directory Operations

### Processing Multiple Files

**Problem**: Process all files in a directory.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\resource\bracket;

$files = Stream(new Directory("data/"))
    ->filter(fn($path) => $path->isFile())
    ->map(fn($path) => Stream($path)
        ->through(bracket())
        ->compile()
        ->toList()
        ->unsafeRunSync()
    )
    ->compile()
    ->toList()
    ->unsafeRunSync();
```

**Discussion**: This recipe shows how to work with directories and process multiple files in a stream.

### File Monitoring

**Problem**: Monitor a directory for changes.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\resource\bracket;

Stream(new Directory("watch/"))
    ->through(watch())
    ->filter(fn($event) => $event->isModified())
    ->map(fn($event) => $event->getPath())
    ->compile()
    ->drain;
```

**Discussion**: The `watch()` function creates a stream that emits events when files in the directory change.

## Best Practices

1. **Resource Management**
   - Always use `bracket` or `Scope` for file operations
   - Handle file permissions appropriately
   - Clean up temporary files

2. **Error Handling**
   - Use `Validation` for file operations
   - Handle file not found cases
   - Implement proper error recovery

3. **Performance**
   - Use appropriate buffer sizes
   - Process large files in chunks
   - Consider using compression for large files

4. **Security**
   - Validate file paths
   - Check file permissions
   - Sanitize file contents

## See Also

- [Resource Management](../resource-management.md)
- [Error Handling](../error-handling.md)
- [Advanced Topics](../advanced-topics.md) 