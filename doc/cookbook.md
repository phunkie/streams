# Phunkie Streams Cookbook

Welcome to the Phunkie Streams Cookbook! This section provides practical recipes and examples for common streaming tasks using Phunkie Streams.

## What's in the Cookbook?

The cookbook is organized into several sections, each focusing on a specific domain of streaming applications:

1. [File Processes](cookbook/file-processes.md)
   - Reading and writing files
   - Processing large files
   - File transformations
   - Directory operations

2. [Network Streams](cookbook/network-streams.md)
   - TCP and UDP streams
   - Half and full duplex sockets
   - Network protocols
   - Connection management

3. [HTTP Streams](cookbook/http-streams.md)
   - HTTP request/response handling
   - Stream context integration
   - REST API clients
   - WebSocket support

4. [System Integration](cookbook/system-integration.md)
   - Process management
   - Signal handling
   - System calls
   - Extension integration (mbstring, json, pcntl, posix, zlib)

## How to Use This Cookbook

Each recipe in the cookbook follows a consistent format:

1. **Problem**: What we're trying to solve
2. **Solution**: The Phunkie Streams code to solve it
3. **Discussion**: Explanation of how it works
4. **See Also**: Related recipes and resources

## Best Practices

When using these recipes, keep in mind these best practices:

1. **Resource Safety**: Always use `bracket` or `Scope` for resource management
2. **Error Handling**: Use `Validation` types for error handling
3. **Backpressure**: Implement appropriate backpressure mechanisms
4. **Testing**: Write tests for your streaming applications

## Contributing

Have a great recipe to share? We welcome contributions! Please see our [Contributing Guide](../CONTRIBUTING.md) for details on how to submit your own recipes. 