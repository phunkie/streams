# Phunkie Streams Cookbook

Welcome to the Phunkie Streams Cookbook! This section provides practical recipes and examples for common streaming tasks using Phunkie Streams.

## What's in the Cookbook?

The cookbook is organized into several sections, each focusing on a specific domain of streaming applications:

1. [File Processes](cookbook/file-processes.md)
   - Reading and writing files with current API
   - File I/O functions (`readFileContents`, `writeFileContents`, etc.)
   - Stream processing with `writeFile()` pipe
   - File transformations and operations
   - Directory batch processing

2. [HTTP Streams](cookbook/http-streams.md)
   - HTTP requests with `Network::httpGet/Post/Put/Delete`
   - Processing JSON APIs
   - REST API client patterns
   - Error handling and retries
   - Pagination and rate limiting
   - Response caching

3. [Network Streams](cookbook/network-streams.md)
   - TCP sockets with `Network::server/client`
   - Echo servers and chat servers
   - Line-based and binary protocols
   - Error handling and performance patterns
   - Testing network code

4. [System Integration](cookbook/system-integration.md)
   - System commands with `bracket()` and `popen()`
   - PHP extension integration (mbstring, json, zlib)
   - Environment variables
   - File system operations
   - Temporary files

## How to Use This Cookbook

Each recipe in the cookbook follows a consistent format:

1. **Problem**: What we're trying to solve
2. **Solution**: The Phunkie Streams code to solve it
3. **Discussion**: Explanation of how it works

## Best Practices

When using these recipes, keep in mind these best practices:

1. **Resource Safety**:
   - Use file I/O functions (`readFileContents()`, etc.) - they handle resources automatically
   - Use `Network::httpGet/Post` for HTTP - resources clean up via `__destruct()`
   - Use `bracket()` from phunkie/effect for custom resource management

2. **Error Handling**:
   - Use `attempt()` for operations that may fail
   - Use `handleError()` for recovery with fallback values
   - Chain operations with `flatMap()` for error propagation

3. **Performance**:
   - Use appropriate buffer sizes for file operations
   - Process large files with `chunk()` to limit memory
   - Use `take()` to limit infinite streams early in the pipeline

4. **Testing**:
   - Test pure transformations separately (easier to test without I/O)
   - Use temporary files for I/O tests
   - Mock network calls or use reliable test endpoints

## What's Currently Implemented

The cookbook examples use the current Phunkie Streams API (Phases 1-3):

- ✅ **File I/O**: All file operations (`readFileContents`, `writeFile` pipe, etc.)
- ✅ **Network**: HTTP requests and TCP sockets
- ✅ **Stream Operations**: `map`, `filter`, `take`, `drop`, `chunk`, `through`, etc.
- ✅ **Error Handling**: `attempt()` and `handleError()`
- ✅ **Resource Management**: `bracket()` and automatic cleanup

**Not Yet Implemented:**
- ❌ **Concurrency**: Parallel processing (planned for Phase 6)
- ❌ **Backpressure**: Flow control mechanisms (planned for Phase 6)
- ❌ **Connection Pooling**: Resource pooling (planned for Phase 6)
- ❌ **Process Management**: Dedicated Process class

## See Also

- [Getting Started Guide](../getting-started.md) - Learn the basics
- [Resource Management Guide](../resource-management.md) - bracket() vs __destruct()
- [Error Handling Guide](../error-handling.md) - Comprehensive error patterns
- [Examples Directory](../../examples/) - Working code examples

## Contributing

Have a great recipe to share? We welcome contributions! Please check if there's a CONTRIBUTING.md file in the repository for details on how to submit your own recipes.
