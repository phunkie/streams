# Phunkie Streams Documentation

Welcome to the official documentation for Phunkie Streams, a PHP functional library for working with streams inspired by functional streaming libraries like fs2 (Scala).

## Getting Started

New to Phunkie Streams? Start here:

1. [Introduction](introduction.md) - Learn what Phunkie Streams is and why you'd use it
2. [Getting Started](getting-started.md) - Installation and first steps
3. [Core Concepts](core-concepts.md) - Understand the fundamental abstractions

## Complete Documentation

### Foundational Topics

1. **[Introduction](introduction.md)**
   - What are Functional Streams?
   - Philosophy and Design Goals
   - Comparison with Other Libraries
   - When to use Phunkie Streams

2. **[Getting Started](getting-started.md)**
   - Installation and Requirements
   - Basic Usage Examples
   - Creating Streams
   - Transforming Data
   - Working with Files and Network
   - Error Handling
   - Resource Management
   - Performance Tips

3. **[Core Concepts](core-concepts.md)**
   - Stream Abstraction and Lifecycle
   - Pull Mechanism (ValuesPull, InfinitePull, ResourcePull, ResourceObjectPull)
   - Scope and Transformation Management (internal)
   - Effect Types (Pure vs IO)

### Stream Types

4. **[Pure Streams](pure-streams.md)**
   - Creating Pure Streams
   - Operations and Transformations
   - Terminal Operations
   - Best Practices

5. **[Infinite Streams](infinite-streams.md)**
   - Creating Infinite Streams with `iterate()` and `unfold()`
   - Safe Processing with `take()` and `takeWhile()`
   - Common Patterns and Mathematical Applications
   - Avoiding Pitfalls

6. **[Working with Streams](working-with-streams.md)**
   - Comprehensive guide to all stream operations
   - Creating, Transforming, Combining, and Consuming
   - Note: Some operations shown are planned for future implementation

### Resource Management

7. **[Resource Streams](resource-streams.md)**
   - Working with Files (line by line, whole contents)
   - Network Resources (HTTP, TCP sockets)
   - Resource Safety Guarantees
   - Error Handling Patterns

8. **[Resource Management Guide](resource-management.md)** ⭐
   - When to use `bracket()` vs `__destruct()`
   - PHP's Deterministic Garbage Collection
   - Resource Class Pattern
   - Decision Guide and Best Practices

### Advanced Topics

9. **[Error Handling Guide](error-handling.md)** ⭐
   - Using `attempt()` and `handleError()`
   - Error Recovery Strategies
   - Validation Pattern Matching
   - Comprehensive Examples

10. **[Composition Guide](composition.md)** ⭐
    - Monadic Composition with `flatMap()`
    - IO Composition Patterns
    - Building Complex Pipelines
    - Best Practices

11. **[Advanced Topics](advanced-topics.md)** ⭐
    - Stream Composition Patterns
    - Complex Data Processing
    - Performance Considerations
    - Testing Stream-Based Code

### Practical Recipes

12. **[Phunkie Streams Cookbook](cookbook.md)**
    - **[File Processes](cookbook/file-processes.md)** - File I/O with current API
    - **[HTTP Streams](cookbook/http-streams.md)** - HTTP requests and REST clients
    - **[Network Streams](cookbook/network-streams.md)** - TCP sockets and servers
    - **[System Integration](cookbook/system-integration.md)** - System commands and PHP extensions

## Quick Reference

### Currently Implemented (Phases 1-3)

- ✅ Pure and Infinite Streams
- ✅ Stream Operations (`map`, `filter`, `take`, `drop`, `chunk`, `through`, etc.)
- ✅ File I/O (all functions: `readFileContents`, `writeFile` pipe, etc.)
- ✅ Network (HTTP via `Network::httpGet/Post`, TCP via `Network::server/client`)
- ✅ Resource Management (bracket + automatic cleanup)
- ✅ Error Handling (`attempt()`, `handleError()`)

### Not Yet Implemented

- ❌ Concurrency (parallel processing)
- ❌ Backpressure (flow control)
- ❌ Connection Pooling
- ❌ Process Management (dedicated Process class)

See [README.md](../README.md) for the complete "What's Implemented" section.

## Examples

Working code examples are available in the [examples directory](../examples/):

- `bracket.php` - Resource management (10 examples)
- `error-handling.php` - Error handling (12 examples)
- `composition.php` - Stream composition (12 examples)
- `stream-operations.php` - Stream operations (20 examples)
- `file-pipes.php` - File I/O with streams (12 examples)
- `network.php` - Network operations (15 examples)

## Contributing

We welcome contributions to Phunkie Streams and its documentation! Check if there's a CONTRIBUTING.md file in the repository for details.

## License

Phunkie Streams is licensed under the LICENSE file included in the repository.

---

⭐ = Updated documentation with current API (Phases 1-3)
