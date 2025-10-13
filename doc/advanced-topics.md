# Advanced Topics

This section covers advanced concepts and patterns for building sophisticated streaming applications with Phunkie Streams.

## Stream Composition Patterns

### Composing Multiple Streams

Phunkie Streams provides several ways to combine multiple streams:

```php
<?php
use function Phunkie\Streams\Stream;

// Concatenation - streams processed sequentially
$stream1 = Stream(1, 2, 3);
$stream2 = Stream(4, 5, 6);
$concatenated = $stream1->concat($stream2);
// Result: [1, 2, 3, 4, 5, 6]

// Merge - combines all streams
$merged = Stream(1, 2, 3)->merge(
    Stream(4, 5, 6),
    Stream(7, 8, 9)
);
// Result: [1, 2, 3, 4, 5, 6, 7, 8, 9]

// Interleave - alternates between streams
$x = Stream(1, 2, 3);
$y = Stream("a", "b", "c");
$z = Stream(true, false, true);
$interleaved = $x->interleave($y, $z);
// Result: [1, "a", true, 2, "b", false, 3, "c", true]

// Zip - pairs elements from streams
$zipped = Stream(1, 2, 3)->zip(Stream("a", "b", "c"));
// Result: [[1, "a"], [2, "b"], [3, "c"]]
```

### Using through() for Pipeline Composition

The `through()` operator allows you to compose transformation pipelines:

```php
<?php
use function Phunkie\Streams\Stream;

// Define reusable transformations
$uppercase = fn(Stream $s) => $s->map(fn($x) => strtoupper($x));
$onlyLong = fn(Stream $s) => $s->filter(fn($x) => strlen($x) > 3);
$addPrefix = fn(Stream $s) => $s->map(fn($x) => ">> $x");

// Compose them
$result = Stream(...['hello', 'hi', 'world', 'bye'])
    ->through($onlyLong)
    ->through($uppercase)
    ->through($addPrefix)
    ->toArray();
// Result: ['>> HELLO', '>> WORLD']

// Or inline
$pipeline = fn(Stream $s) => $s
    ->filter(fn($x) => strlen($x) > 3)
    ->map(fn($x) => strtoupper($x))
    ->map(fn($x) => ">> $x");

$result = Stream(...['hello', 'hi', 'world'])
    ->through($pipeline)
    ->toArray();
```

## Complex Stream Processing

### Multi-Stage Data Processing

Combine file I/O, transformations, and output:

```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\IO\File\{writeFile, readLines};
use Phunkie\Streams\IO\File\Path;

// Read CSV, transform, write JSON
$processCSV = fn(Stream $s) => $s
    ->map(fn($line) => str_getcsv($line))
    ->filter(fn($row) => !empty($row[0]))
    ->map(fn($row) => [
        'id' => $row[0],
        'name' => $row[1],
        'value' => (int)$row[2]
    ])
    ->filter(fn($item) => $item['value'] > 100)
    ->map(fn($item) => json_encode($item));

Stream(new Path('input.csv'))
    ->through($processCSV)
    ->through(writeFile(new Path('output.jsonl')));
```

### Network Data Processing

Process HTTP responses with complex transformations:

```php
<?php
use Phunkie\Streams\Network;

$processAPIData = fn($chunk) => json_decode($chunk, true);
$extractUsers = fn($data) => $data['users'] ?? [];
$onlyActive = fn($user) => $user['active'] === true;

$activeUsers = Network::httpGet('https://api.example.com/data')
    ->map($processAPIData)
    ->filter(fn($data) => $data !== null)
    ->flatMap(fn($data) => Stream(...$extractUsers($data)))
    ->filter($onlyActive)
    ->compile->toArray();
```

## Performance Considerations

### Memory Management with Large Datasets

When working with large files or infinite streams, manage memory carefully:

```php
<?php
use function Phunkie\Streams\Stream;
use Phunkie\Streams\IO\File\Path;

// Process large files in chunks
Stream(new Path('huge-file.csv'))
    ->chunk(1000)  // Process 1000 lines at a time
    ->map(fn($chunk) => processChunk($chunk))
    ->through(writeResults())
    ->compile->drain
    ->unsafeRunSync();

// Limit infinite streams
Stream(iterate(0)(fn($x) => $x + 1))
    ->filter(fn($x) => isPrime($x))
    ->take(100)  // Only take first 100 primes
    ->compile->toList();
```

### Buffer Sizes

Optimize buffer sizes based on your use case:

```php
<?php
use function Phunkie\Streams\Stream;
use Phunkie\Streams\IO\File\Path;

// Small buffer for text processing (default 4096)
$textStream = Stream(new Path('text.txt'));

// Larger buffer for binary data
$binaryStream = Stream(new Path('binary.dat'), 8192);

// Very large buffer for high-throughput
$largeStream = Stream(new Path('large.dat'), 65536);
```

## Error Handling Patterns

For comprehensive error handling patterns, see [Error Handling Guide](error-handling.md). Here are some advanced patterns:

### Fallback Chains

Try multiple sources with fallback:

```php
<?php
use Phunkie\Streams\Network;
use function Phunkie\Streams\IO\File\readFileContents;
use Phunkie\Streams\IO\File\Path;

// Try primary API, fall back to cache, fall back to default
$data = Network::httpGet('https://api.example.com/data')
    ->map(fn($chunk) => json_decode($chunk, true))
    ->attempt()
    ->flatMap(fn($result) => $result->getOrElse(
        readFileContents(new Path('cache.json'))
            ->map(fn($json) => json_decode($json, true))
            ->attempt()
            ->map(fn($cacheResult) => $cacheResult->getOrElse(['default' => true]))
    ))
    ->unsafeRunSync();
```

### Custom Error Recovery

Implement domain-specific error handling:

```php
<?php
use function Phunkie\Streams\IO\File\readFileContents;
use Phunkie\Streams\IO\File\Path;

class DataProcessingError extends \Exception {
    public function __construct(public readonly string $file, \Throwable $previous) {
        parent::__construct("Failed to process $file", 0, $previous);
    }
}

$processFile = function(string $filename) {
    return readFileContents(new Path($filename))
        ->handleError(fn($e) => throw new DataProcessingError($filename, $e))
        ->map(fn($content) => processContent($content))
        ->unsafeRunSync();
};
```

## Testing Stream-Based Code

### Unit Testing Pure Streams

```php
<?php
use PHPUnit\Framework\TestCase;
use function Phunkie\Streams\Stream;

class StreamProcessingTest extends TestCase
{
    public function testTransformationPipeline()
    {
        $result = Stream(1, 2, 3, 4, 5)
            ->filter(fn($x) => $x % 2 === 0)
            ->map(fn($x) => $x * 2)
            ->toArray();

        $this->assertEquals([4, 8], $result);
    }

    public function testChunking()
    {
        $result = Stream(...range(1, 6))
            ->chunk(2)
            ->toArray();

        $this->assertEquals([[1, 2], [3, 4], [5, 6]], $result);
    }
}
```

### Testing Resource Streams

```php
<?php
use PHPUnit\Framework\TestCase;
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\IO\File\{writeFileContents, readFileContents};
use Phunkie\Streams\IO\File\Path;

class ResourceStreamTest extends TestCase
{
    private Path $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = new Path(tempnam(sys_get_temp_dir(), 'test'));
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile->toString())) {
            unlink($this->tempFile->toString());
        }
    }

    public function testFileRoundtrip()
    {
        $data = "Hello, World!";

        // Write
        writeFileContents($this->tempFile, $data)->unsafeRunSync();

        // Read
        $result = readFileContents($this->tempFile)->unsafeRunSync();

        $this->assertEquals($data, $result);
    }

    public function testStreamPipeline()
    {
        $lines = ['line1', 'line2', 'line3'];

        Stream(...$lines)
            ->map(fn($line) => strtoupper($line))
            ->through(writeFile($this->tempFile));

        $result = Stream($this->tempFile)
            ->compile->toArray();

        $this->assertEquals(['LINE1', 'LINE2', 'LINE3'], $result);
    }
}
```

### Testing Network Operations

```php
<?php
use PHPUnit\Framework\TestCase;
use Phunkie\Streams\Network;

class NetworkTest extends TestCase
{
    public function testHttpGet()
    {
        // Use a reliable test endpoint
        $response = Network::httpGet('http://httpbin.org/get')
            ->compile->toArray();

        $this->assertNotEmpty($response);

        $body = implode('', $response);
        $data = json_decode($body, true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('url', $data);
    }

    public function testHttpError()
    {
        $result = Network::httpGet('http://httpbin.org/status/404')
            ->map(fn($chunk) => $chunk)
            ->attempt()
            ->unsafeRunSync();

        // Even 404s return response body
        $this->assertTrue($result->isSuccess() || $result->isFailure());
    }
}
```

## Best Practices

### Resource Management

1. **Use the provided file I/O functions** - `readFileContents()`, `writeFileContents()`, etc.
2. **Use the Network API for HTTP/sockets** - `Network::httpGet()`, `Network::client()`, etc.
3. **Trust automatic cleanup** - Resource objects use `__destruct()` for cleanup
4. **Use bracket() for custom resources** - When working with raw file handles
5. **See [Resource Management Guide](resource-management.md)** for detailed patterns

### Error Handling

1. **Use `attempt()` for IO operations** - Converts exceptions to Validation
2. **Use `handleError()` for recovery** - Provide fallback values or behavior
3. **Chain error handling** - Use `flatMap()` to recover from multiple operations
4. **See [Error Handling Guide](error-handling.md)** for comprehensive patterns

### Performance

1. **Use appropriate chunk sizes** - Balance memory usage and processing efficiency
2. **Use `take()` to limit infinite streams** - Always limit before consuming
3. **Process in stages** - Break complex transformations into smaller steps
4. **Profile your code** - Identify bottlenecks before optimizing

### Composition

1. **Extract reusable transformations** - Use `through()` with named functions
2. **Keep operations pure** - Avoid side effects in `map()` and `filter()`
3. **Compose with `flatMap()`** - Chain IO operations safely
4. **Use pipes for complex workflows** - `writeFile()`, `Network::socketWrite()`, etc.

### Testing

1. **Test pure transformations separately** - Easier to test without I/O
2. **Use temporary files for I/O tests** - Clean up in `tearDown()`
3. **Mock network calls in integration tests** - Or use reliable test endpoints
4. **Test error cases** - Verify recovery and cleanup behavior

## See Also

- [Resource Management Guide](resource-management.md) - bracket() vs __destruct() patterns
- [Error Handling Guide](error-handling.md) - Comprehensive error handling patterns
- [Composition Guide](composition.md) - Monadic composition with flatMap()
- [Resource Streams](resource-streams.md) - Working with files and network
