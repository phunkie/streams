# Phunkie Streams Documentation

Welcome to the official documentation for Phunkie Streams, a PHP functional library for working with streams inspired by functional streaming libraries like fs2 (Scala).

## Table of Contents

1. [Introduction](introduction.md)
   - What are Functional Streams?
   - Philosophy and Design Goals
   - Comparison with Other Libraries
   - When to use Phunkie Streams

2. [Getting Started](getting-started.md)
   - Installation
   - Basic Usage
   - Configuration

3. [Core Concepts](core-concepts.md)
   - Stream Abstraction
   - Pull Mechanism
   - Scope and Resource Management
   - Effect Types (Pure, IO)

4. [Working with Streams](working-with-streams.md)
   - Creating Streams
   - Transforming Streams
   - Combining Streams
   - Consuming Streams

5. [Pure Streams](pure-streams.md)
   - Creating Pure Streams
   - Operations on Pure Streams
   - Converting to Other Collections

6. [Infinite Streams](infinite-streams.md)
   - Creating Infinite Streams
   - Safe Processing with take() and compile()
   - Common Patterns and Pitfalls

7. [Resource Streams](resource-streams.md)
   - Working with Files
   - Network Resources
   - Resource Safety

8. [Phunkie Streams Cookbook](cookbook.md)
   - [File Processes](cookbook/file-processes.md)
   - [Network Streams](cookbook/network-streams.md)
   - [HTTP Streams](cookbook/http-streams.md)
   - [System Integration](cookbook/system-integration.md)

## Contributing

We welcome contributions to Phunkie Streams and its documentation! Please see our [Contributing Guide](../CONTRIBUTING.md) for more information.

## License

Phunkie Streams is licensed under the [LICENSE](../LICENSE) file included in the repository. 