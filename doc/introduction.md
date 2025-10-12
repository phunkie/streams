# Introduction to Phunkie Streams

## What are Functional Streams?

Functional streams are a programming abstraction that represent sequences of data as a series of transformations. Unlike traditional collections (arrays, lists) which load all data into memory at once, streams:

- Process data elements one at a time
- Evaluate operations lazily (only when needed)
- Chain transformations in a declarative way
- Support processing potentially infinite data sets
- Enable pure functional composition

In functional programming, streams provide a powerful way to process data by composing small, reusable operations rather than writing imperative loops. This leads to code that is more expressive, maintainable, and often more efficient.

## Philosophy and Design Goals

Phunkie Streams follows several core design principles:

### Functional Purity

The library emphasizes pure functions where possible, avoiding side effects and mutable state. This leads to code that is easier to reason about, test, and parallelize.

### Composition Over Inheritance

Streams are designed to be composed through a chain of operations, rather than extending base classes. This approach encourages building complex behavior from simple building blocks.

### Laziness and Efficiency

Operations on streams are evaluated lazily—only when a terminal operation requests a result. This allows Phunkie Streams to:

- Avoid unnecessary computations
- Work with potentially infinite data sources
- Use memory efficiently by not holding entire collections in memory

### Type Safety

Phunkie Streams embraces PHP's type system where possible, helping catch errors at development time rather than runtime.

### Resource Safety

The library includes built-in resource management capabilities to ensure that file handles, network connections, and other resources are properly acquired and released, even in the presence of errors.

## Comparison with Other Libraries

### Phunkie Streams vs. PHP Native Functions

While PHP provides native functions like `array_map`, `array_filter`, and `array_reduce`, these:

- Operate only on arrays, not streams
- Load entire collections into memory
- Execute eagerly, not lazily
- Lack composable interfaces

Phunkie Streams offers a more functional, composable, and memory-efficient alternative.

### Phunkie Streams vs. Functional Libraries in Other Languages

Phunkie Streams draws inspiration from several functional streaming libraries:

| Library | Language | Similarities | Differences |
|---------|----------|--------------|-------------|
| fs2 | Scala | Functional approach, laziness, resource safety | Phunkie adapts concepts to PHP's type system and lacks static typing guarantees |
| RxPHP | PHP | Streaming operations, compositions | RxPHP focuses on reactive programming and event streams, while Phunkie prioritizes data processing |
| Java Streams | Java | Lazy evaluation, functional operations | Java Streams are more integrated with the language's type system and have richer parallelism |

Phunkie Streams aims to bring the elegance and power of functional streaming libraries to PHP, while working within PHP's dynamic typing system and language capabilities.

### Phunkie Streams vs. PHP Native Streaming

While PHP provides native streaming capabilities through `stream_*` functions, `curl_*` functions, and file wrappers, these have several limitations that Phunkie Streams addresses:

#### Resource Management
- **PHP Native**: Manual resource management with `fopen`/`fclose`, `curl_init`/`curl_close`
- **Phunkie Streams**: Automatic resource cleanup using the `bracket` pattern and `Scope`

#### Error Handling
- **PHP Native**: Error handling through exceptions or return values
- **Phunkie Streams**: Type-safe error handling using `Validation` types

#### Composition
- **PHP Native**: Stream operations are not easily composable
- **Phunkie Streams**: Streams can be composed using functional operations like `map`, `filter`, and `flatMap`

#### Backpressure
- **PHP Native**: No built-in backpressure mechanisms
- **Phunkie Streams**: Built-in backpressure support for handling high-throughput streams

#### Testing
- **PHP Native**: Difficult to test due to side effects and resource management
- **Phunkie Streams**: Easier to test with pure functions and explicit resource management

#### Example: Reading a File
```php
// PHP Native
$handle = fopen("file.txt", "r");
if ($handle) {
    while (($line = fgets($handle)) !== false) {
        processLine($line);
    }
    fclose($handle);
}

// Phunkie Streams
Stream(new Path("file.txt"))
    ->through(bracket())
    ->map(fn($line) => processLine($line))
    ->compile()
    ->drain;
```

#### Example: HTTP Request
```php
// PHP Native with curl
$ch = curl_init("https://api.example.com/data");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
if ($response === false) {
    handleError(curl_error($ch));
}
curl_close($ch);
processResponse($response);

// Phunkie Streams
Stream(new HttpRequest("GET", "https://api.example.com/data"))
    ->through(bracket())
    ->map(fn($response) => processResponse($response))
    ->compile()
    ->drain;
```

#### Example: Process Output
```php
// PHP Native
$handle = popen("command", "r");
if ($handle) {
    while (($line = fgets($handle)) !== false) {
        processOutput($line);
    }
    pclose($handle);
}

// Phunkie Streams
Stream(new Process("command"))
    ->through(bracket())
    ->map(fn($line) => processOutput($line))
    ->compile()
    ->drain;
```

Phunkie Streams provides a more functional, composable, and resource-safe alternative to PHP's native streaming capabilities, making it easier to build robust streaming applications.

## When to Use Phunkie Streams

Phunkie Streams is ideal for:

- Processing large data sets without loading everything into memory
- Creating data transformation pipelines
- Working with potentially infinite data sources
- Building pure functional code in PHP
- Safely managing resources like files and network connections

By embracing functional programming principles, Phunkie Streams allows PHP developers to write more declarative, composable, and maintainable code. 