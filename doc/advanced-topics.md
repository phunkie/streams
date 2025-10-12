# Advanced Topics

This section covers advanced concepts and features of Phunkie Streams that will help you build more sophisticated streaming applications.

## Custom Pull Implementations

Phunkie Streams allows you to create custom Pull implementations for specialized use cases. A Pull is the core abstraction that represents a computation that can be run to produce values.

### Creating a Custom Pull

```php
<?php
use Phunkie\Streams\Pull;
use Phunkie\Streams\Stream;

class CustomPull extends Pull
{
    private $generator;
    
    public function __construct(callable $generator)
    {
        $this->generator = $generator;
    }
    
    public function getValues(): \Generator
    {
        return ($this->generator)();
    }
}

// Using the custom pull
$stream = Stream(new CustomPull(function() {
    // Custom value generation logic
    for ($i = 0; $i < 10; $i++) {
        yield $i * 2;
    }
}));
```

### Pull Composition

Pulls can be composed to create more complex streaming behaviors:

```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\pull\compose;

$pull1 = new CustomPull(fn() => yield from range(1, 5));
$pull2 = new CustomPull(fn() => yield from range(6, 10));

$composedPull = compose($pull1, $pull2);
$stream = Stream($composedPull);
```

## Performance Considerations

### Memory Management

When working with large datasets, it's important to consider memory usage:

1. **Use `take()` for Infinite Streams**:
```php
<?php
$stream = Stream(new InfinitePull())
    ->take(1000)  // Limit the number of elements
    ->compile()
    ->toList();
```

2. **Process in Chunks**:
```php
<?php
$stream = Stream(new Path("large-file.txt"))
    ->chunk(1000)  // Process 1000 lines at a time
    ->map(fn($chunk) => processChunk($chunk))
    ->compile()
    ->drain;
```

### Buffer Sizes

Optimize buffer sizes for different use cases:

```php
<?php
// Small buffer for text processing
$textStream = Stream(new Path("text.txt"), 1024);

// Larger buffer for binary data
$binaryStream = Stream(new Path("binary.dat"), 8192);
```

## Testing Stream-based Code

### Unit Testing Streams

```php
<?php
use PHPUnit\Framework\TestCase;
use function Phunkie\Streams\Stream;

class StreamTest extends TestCase
{
    public function testStreamTransformation()
    {
        $stream = Stream([1, 2, 3, 4, 5])
            ->map(fn($x) => $x * 2)
            ->filter(fn($x) => $x > 5)
            ->compile()
            ->toList()
            ->unsafeRunSync();
            
        $this->assertEquals([6, 8, 10], $stream);
    }
    
    public function testResourceStream()
    {
        $scope = new ResourceScope();
        $stream = Stream(new Path("test.txt"))
            ->through($scope->register(fn() => $resource))
            ->compile()
            ->toList();
            
        // Verify resource cleanup
        $this->assertTrue($scope->isClosed());
    }
}
```

### Property-Based Testing

```php
<?php
use Phunkie\PropertyTesting\Property;
use function Phunkie\Streams\Stream;

class StreamProperties extends Property
{
    public function testStreamIdentity()
    {
        $this->forAll(
            $this->genList($this->genInt())
        )->then(function($list) {
            $stream = Stream($list)
                ->compile()
                ->toList()
                ->unsafeRunSync();
                
            return $stream === $list;
        });
    }
}
```

## Error Handling

### Custom Error Types

```php
<?php
use Phunkie\Validation\Validation;
use function Phunkie\PatternMatching\Referenced\{Success, Failure};
use function pmatch;

class StreamError extends \Exception {}

$result = Stream(new Path("file.txt"))
    ->through(attempt())
    ->map(function($value) {
        if (!$value) {
            throw new StreamError("Invalid value");
        }
        return $value;
    })
    ->compile()
    ->toList()
    ->unsafeRunSync();

$on = pmatch($result);
match(true) {
    $on(Success($data)) => processData($data),
    $on(Failure($error)) => handleError($error)
};
```

### Error Recovery

```php
<?php
$stream = Stream(new Path("file.txt"))
    ->through(attempt())
    ->recover(function($error) {
        if ($error instanceof FileNotFoundError) {
            return Stream([]);  // Return empty stream for missing files
        }
        throw $error;  // Re-throw other errors
    })
    ->compile()
    ->toList();
```

## Advanced Stream Operations

### Custom Stream Operators

```php
<?php
use Phunkie\Streams\Stream;
use Phunkie\Streams\Pull;

function window(int $size): callable
{
    return function(Stream $stream) use ($size) {
        return $stream->through(function(Pull $pull) use ($size) {
            $buffer = [];
            return new Pull(function() use ($pull, $buffer, $size) {
                while (count($buffer) < $size) {
                    $value = $pull->getValues()->current();
                    if ($value === null) break;
                    $buffer[] = $value;
                    $pull->getValues()->next();
                }
                if (count($buffer) === $size) {
                    $result = $buffer;
                    array_shift($buffer);
                    return $result;
                }
                return null;
            });
        });
    };
}

// Usage
$stream = Stream(range(1, 10))
    ->through(window(3))
    ->compile()
    ->toList();
// Result: [[1,2,3], [2,3,4], [3,4,5], ...]
```

### Stream Composition

```php
<?php
use function Phunkie\Streams\Stream;
use function Phunkie\Streams\Functions\stream\merge;

// Merge multiple streams
$stream1 = Stream([1, 2, 3]);
$stream2 = Stream([4, 5, 6]);
$merged = merge($stream1, $stream2)
    ->compile()
    ->toList();

// Zip streams
$zipped = $stream1->zip($stream2)
    ->compile()
    ->toList();
// Result: [[1,4], [2,5], [3,6]]
```

## Best Practices

1. **Resource Management**
   - Always use `bracket` or `Scope` for resource management
   - Ensure proper cleanup in error cases
   - Use appropriate buffer sizes

2. **Error Handling**
   - Use `Validation` instead of exceptions where possible
   - Implement proper error recovery strategies
   - Log errors appropriately

3. **Performance**
   - Use appropriate chunk sizes for large datasets
   - Implement backpressure mechanisms for high-throughput streams
   - Profile and optimize hot paths

4. **Testing**
   - Write comprehensive unit tests
   - Use property-based testing for stream operations
   - Test error cases and edge conditions

5. **Composition**
   - Keep stream operations pure where possible
   - Use function composition for complex transformations
   - Implement custom operators for reusable patterns 