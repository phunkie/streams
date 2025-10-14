# File Processes

This section provides recipes for working with files using Phunkie Streams with the current API.

## Reading Files

### Basic File Reading

**Problem**: Read a file line by line.

**Solution**:
```php
<?php
use Phunkie\Streams\IO\File\Path;

$lines = Stream(new Path("file.txt"))
    ->compile()
    ->toList()
    ->unsafeRunSync();
```

**Discussion**: `Stream(new Path(...))` creates a stream that reads the file line by line. Resources are automatically cleaned up when the stream completes.

### Reading Entire File Contents

**Problem**: Read an entire file into a string.

**Solution**:
```php
<?php
use function Phunkie\Streams\IO\File\readFileContents;
use Phunkie\Streams\IO\File\Path;

$content = readFileContents(new Path("file.txt"))
    ->unsafeRunSync();
```

**Discussion**: `readFileContents()` uses bracket internally for guaranteed cleanup. Returns an `IO<string>`.

### Reading File as Array of Lines

**Problem**: Read all lines into an array.

**Solution**:
```php
<?php
use function Phunkie\Streams\IO\File\readLines;
use Phunkie\Streams\IO\File\Path;

$lines = readLines(new Path("file.txt"))
    ->unsafeRunSync();
```

**Discussion**: `readLines()` reads the file and returns an array of lines, using bracket for safe resource management.

### Reading Large Files

**Problem**: Process a large file without loading it entirely into memory.

**Solution**:
```php
<?php
use Phunkie\Streams\IO\File\Path;

$processed = Stream(new Path("large-file.txt"))
    ->chunk(1000)  // Process 1000 lines at a time
    ->map(fn($chunk) => processChunk($chunk))
    ->compile()
    ->drain
    ->unsafeRunSync();
```

**Discussion**: By using `chunk()`, we process the file in manageable pieces, preventing memory issues with large files.

### Reading with Custom Buffer Size

**Problem**: Optimize performance for specific file types.

**Solution**:
```php
<?php
use Phunkie\Streams\IO\File\Path;

// Small buffer for text files (default is 4096)
$textStream = Stream(new Path("text.txt"));

// Large buffer for binary files
$binaryStream = Stream(new Path("video.mp4"), 65536);

$lines = $textStream->compile()->toArray();
```

**Discussion**: Adjust buffer size based on file type and use case for optimal performance.

## Writing Files

### Basic File Writing

**Problem**: Write data to a file.

**Solution**:
```php
<?php
use function Phunkie\Streams\IO\File\writeFileContents;
use Phunkie\Streams\IO\File\Path;

$bytes = writeFileContents(new Path("output.txt"), "Hello, World!")
    ->unsafeRunSync();
```

**Discussion**: `writeFileContents()` uses bracket internally for guaranteed cleanup. Returns an `IO<int>` with bytes written.

### Writing Array of Lines

**Problem**: Write multiple lines to a file.

**Solution**:
```php
<?php
use function Phunkie\Streams\IO\File\writeLines;
use Phunkie\Streams\IO\File\Path;

$lines = ["line1", "line2", "line3"];
$bytes = writeLines(new Path("output.txt"), $lines)
    ->unsafeRunSync();
```

**Discussion**: `writeLines()` writes each array element as a separate line with proper line endings.

### Streaming Write with Pipe

**Problem**: Write a stream of values to a file.

**Solution**:
```php
<?php
use function Phunkie\Streams\IO\File\writeFile;
use Phunkie\Streams\IO\File\Path;

Stream("line1", "line2", "line3")
    ->through(writeFile(new Path("output.txt")));
```

**Discussion**: `writeFile()` is a pipe function that writes stream elements to a file. Perfect for transforming data before writing.

### Writing with Transformations

**Problem**: Transform data before writing to file.

**Solution**:
```php
<?php
use function Phunkie\Streams\IO\File\writeFile;
use Phunkie\Streams\IO\File\Path;

Stream(1, 2, 3, 4, 5)
    ->filter(fn($x) => $x % 2 === 0)
    ->map(fn($x) => "Even: $x")
    ->through(writeFile(new Path("evens.txt")));
```

**Discussion**: Chain transformations before writing. Each element is converted to string automatically.

## File Transformations

### CSV to JSON Conversion

**Problem**: Convert a CSV file to JSON format.

**Solution**:
```php
<?php
use function Phunkie\Streams\IO\File\writeFile;
use Phunkie\Streams\IO\File\Path;

Stream(new Path("data.csv"))
    ->map(fn($line) => str_getcsv($line))
    ->filter(fn($row) => !empty($row[0]))
    ->map(fn($row) => [
        "id" => $row[0],
        "name" => $row[1],
        "value" => (int)$row[2]
    ])
    ->map(fn($data) => json_encode($data))
    ->through(writeFile(new Path("output.jsonl")));
```

**Discussion**: Process CSV line by line, transform to structured data, and write as JSON lines.

### File Filtering

**Problem**: Filter and copy specific lines from a file.

**Solution**:
```php
<?php
use function Phunkie\Streams\IO\File\writeFile;
use Phunkie\Streams\IO\File\Path;

Stream(new Path("input.log"))
    ->filter(fn($line) => str_contains($line, 'ERROR'))
    ->map(fn($line) => trim($line))
    ->through(writeFile(new Path("errors.log")));
```

**Discussion**: Stream-based filtering is memory-efficient for large files.

### Case Conversion

**Problem**: Convert file contents to uppercase.

**Solution**:
```php
<?php
use function Phunkie\Streams\IO\File\{readFileContents, writeFileContents};
use Phunkie\Streams\IO\File\Path;

$result = readFileContents(new Path("input.txt"))
    ->map(fn($content) => strtoupper($content))
    ->flatMap(fn($upper) => writeFileContents(new Path("output.txt"), $upper))
    ->unsafeRunSync();
```

**Discussion**: Use `flatMap()` to chain IO operations safely.

### File Compression

**Problem**: Compress a file using gzip.

**Solution**:
```php
<?php
use function Phunkie\Streams\IO\File\{readFileContents, writeFileContents};
use Phunkie\Streams\IO\File\Path;

$compressFile = function(Path $input, Path $output) {
    return readFileContents($input)
        ->map(fn($content) => gzcompress($content, 9))
        ->flatMap(fn($compressed) => writeFileContents($output, $compressed));
};

$compressFile(
    new Path("large.txt"),
    new Path("large.txt.gz")
)->unsafeRunSync();
```

**Discussion**: Chain read and write operations with compression in between.

## File Operations

### Copying Files

**Problem**: Copy a file to a new location.

**Solution**:
```php
<?php
use function Phunkie\Streams\IO\File\writeFile;
use Phunkie\Streams\IO\File\Path;

Stream(new Path("source.txt"))
    ->through(writeFile(new Path("destination.txt")));
```

**Discussion**: Streaming copy is memory-efficient, even for large files.

### File Existence Check

**Problem**: Check if a file exists before processing.

**Solution**:
```php
<?php
use function Phunkie\Streams\IO\File\{exists, readFileContents};
use Phunkie\Streams\IO\File\Path;

$path = new Path("data.txt");

$content = exists($path)
    ->flatMap(fn($fileExists) =>
        $fileExists
            ? readFileContents($path)
            : io(fn() => "File not found")
    )
    ->unsafeRunSync();
```

**Discussion**: Combine `exists()` with `flatMap()` for conditional file operations.

### Deleting Files

**Problem**: Delete a file safely.

**Solution**:
```php
<?php
use function Phunkie\Streams\IO\File\deleteFile;
use Phunkie\Streams\IO\File\Path;

deleteFile(new Path("temp.txt"))
    ->unsafeRunSync();
```

**Discussion**: `deleteFile()` returns `IO<Unit>` for safe deletion.

### Temporary Files

**Problem**: Create and clean up temporary files.

**Solution**:
```php
<?php
use function Phunkie\Streams\IO\File\{writeFileContents, readFileContents, deleteFile};
use Phunkie\Streams\IO\File\Path;

$tempPath = new Path(tempnam(sys_get_temp_dir(), 'stream_'));

$result = writeFileContents($tempPath, "temporary data")
    ->flatMap(fn($_) => readFileContents($tempPath))
    ->map(fn($content) => processContent($content))
    ->flatMap(fn($result) => deleteFile($tempPath)->map(fn($_) => $result))
    ->unsafeRunSync();
```

**Discussion**: Chain operations and ensure cleanup with `deleteFile()`.

## Directory Operations

### Processing Multiple Files

**Problem**: Process all files in a directory.

**Solution**:
```php
<?php
use function Phunkie\Streams\IO\File\readFileContents;
use Phunkie\Streams\IO\File\Path;

// Get all .txt files from directory
$files = array_filter(
    scandir('/path/to/directory'),
    fn($file) => str_ends_with($file, '.txt') && $file !== '.' && $file !== '..'
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

**Discussion**: Use PHP's `scandir()` with stream processing for batch file operations.

### Aggregating File Data

**Problem**: Combine data from multiple files.

**Solution**:
```php
<?php
use function Phunkie\Streams\IO\File\readLines;
use Phunkie\Streams\IO\File\Path;

$files = ['data1.txt', 'data2.txt', 'data3.txt'];

$allLines = Stream(...$files)
    ->map(fn($file) => new Path($file))
    ->flatMap(fn($path) => Stream(...readLines($path)->unsafeRunSync()))
    ->filter(fn($line) => !empty(trim($line)))
    ->toArray();
```

**Discussion**: Use `flatMap()` to merge streams from multiple files.

## Error Handling

### Handling File Not Found

**Problem**: Gracefully handle missing files.

**Solution**:
```php
<?php
use function Phunkie\Streams\IO\File\readFileContents;
use Phunkie\Streams\IO\File\Path;

$content = readFileContents(new Path('/nonexistent/file.txt'))
    ->attempt()
    ->map(fn($validation) => $validation->getOrElse("default content"))
    ->unsafeRunSync();
```

**Discussion**: Use `attempt()` to convert exceptions to Validation for safe error handling.

### Fallback Chain

**Problem**: Try multiple file sources.

**Solution**:
```php
<?php
use function Phunkie\Streams\IO\File\{readFileContents, exists};
use Phunkie\Streams\IO\File\Path;

$tryFiles = function(array $paths) {
    foreach ($paths as $path) {
        $fileExists = exists($path)->unsafeRunSync();
        if ($fileExists) {
            return readFileContents($path);
        }
    }
    return io(fn() => throw new \RuntimeException("No files found"));
};

$content = $tryFiles([
    new Path('config.local.php'),
    new Path('config.php'),
    new Path('config.default.php')
])->unsafeRunSync();
```

**Discussion**: Iterate through potential file locations until one is found.

## Best Practices

1. **Resource Management**
   - Use file I/O functions (`readFileContents()`, `writeFileContents()`, etc.) - they handle resources automatically
   - Use `Stream(new Path(...))` for line-by-line processing
   - Clean up temporary files with `deleteFile()`
   - Trust automatic resource cleanup

2. **Error Handling**
   - Use `attempt()` for operations that may fail
   - Use `exists()` to check before reading
   - Provide fallback values with `getOrElse()`
   - Chain operations with `flatMap()` for error propagation

3. **Performance**
   - Use appropriate buffer sizes (default 4096 is good for text)
   - Process large files with `chunk()` to limit memory
   - Stream processing is more memory-efficient than loading entire files
   - Use larger buffers (65536+) for binary files

4. **Security**
   - Validate file paths before operations
   - Check file permissions
   - Sanitize file contents when processing user input
   - Use absolute paths to avoid directory traversal issues

## See Also

- [Resource Management](../resource-management.md) - bracket() vs __destruct() patterns
- [Error Handling](../error-handling.md) - Comprehensive error handling
- [Resource Streams](../resource-streams.md) - Deep dive into file I/O
- [Getting Started](../getting-started.md) - File I/O basics
