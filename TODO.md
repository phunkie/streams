# Phunkie Streams TODO

## Project Vision
Evolve Phunkie Streams towards a feature set and capabilities inspired by functional streaming libraries like fs2 (Scala), focusing on robust, effectful, and concurrent stream processing, **leveraging phunkie/effect** for IO and resource management.

---

## Phase 1: Integration with Phunkie Effect ✅ COMPLETED
- [x] Migrated from local IO to phunkie/effect IO
- [x] Updated all imports and usages
- [x] Fixed API compatibility (run() → unsafeRun())
- [x] All tests passing (25 tests, 28 assertions)

---

## Phase 2: Use Existing Phunkie Effect Features (HIGH PRIORITY)

### 2.1 Import and use bracket() from phunkie/effect
**Status:** Already available in phunkie/effect, needs integration

- [ ] Create `src/Functions/resource.php` with re-exported bracket
- [ ] Update Stream file operations to use `bracket()` pattern
- [ ] Add bracket examples in `examples/bracket.php`
- [ ] Update documentation to show bracket usage
- [ ] Add tests for bracket with streams (`tests/Feature/Streams/BracketSpec.php`)

### 2.2 Use attempt() and handleError() from IO
**Status:** Already available on IO class

- [ ] Update documentation showing `.attempt()` usage patterns
- [ ] Create error handling examples (`examples/error-handling.php`)
- [ ] Add `.handleError()` integration examples
- [ ] Add comprehensive error handling tests
- [ ] Document error recovery strategies

### 2.3 Use flatMap() for stream composition
**Status:** Already available via MonadOps trait on IO

- [ ] Update examples to use `.flatMap()` for IO composition
- [ ] Add documentation for monadic stream composition
- [ ] Create nested stream examples
- [ ] Add tests for complex stream compositions

---

## Phase 3: Implement Missing Stream-Specific Operations (HIGH PRIORITY)

### 3.1 Core stream operations
**These are stream-specific and need to be implemented in phunkie/streams:**

- [ ] **through()** - Pipe operator for stream transformations
  ```php
  Stream(...)->through(fn($s) => $s->map(...)->filter(...))
  ```
  - [ ] Implement in `src/Ops/Stream/` or `src/Type/Stream.php`
  - [ ] Add tests
  - [ ] Add examples

- [ ] **takeWhile(callable $predicate)** - Take while predicate is true
  - [ ] Implement in appropriate Pull ops trait
  - [ ] Add tests
  - [ ] Add examples

- [ ] **dropWhile(callable $predicate)** - Drop while predicate is true
  - [ ] Implement in appropriate Pull ops trait
  - [ ] Add tests
  - [ ] Add examples

- [ ] **chunk(int $size)** - Process in fixed-size chunks
  - [ ] Implement chunking logic
  - [ ] Add tests
  - [ ] Add examples

- [ ] **merge(Stream ...$streams)** - Merge multiple streams
  - [ ] Implement merge logic
  - [ ] Add tests
  - [ ] Add examples

- [ ] **zip(Stream $other)** - Zip two streams (plain zip, zipWith exists)
  - [ ] Implement in stream ops
  - [ ] Add tests
  - [ ] Add examples

- [ ] **drain** - Fix property accessor (currently works but needs review)
  - [ ] Review current implementation
  - [ ] Ensure it returns IO properly
  - [ ] Add tests

### 3.2 File I/O stream operations
**File operations using bracket from phunkie/effect:**

- [ ] Create `src/Functions/file.php` with:
  - [ ] `writeFile(Path $path): callable` - Returns pipe function using bracket
  - [ ] `readFile(Path $path, int $bufferSize = 4096): Pull` - Returns Pull with bracket
  - [ ] `exists(Path $path): IO<bool>` - Check if file exists
  - [ ] `deleteFile(Path $path): IO<Unit>` - Delete file safely

- [ ] Add comprehensive file I/O tests
- [ ] Add file processing examples
- [ ] Update documentation with file operations

### 3.3 Network stream operations (OPTIONAL)
**HTTP and socket operations - may need external libraries:**

- [ ] Evaluate need for network operations
- [ ] If needed, create `src/Functions/network.php` with:
  - [ ] `httpGet(string $url): Pull`
  - [ ] `httpPost(string $url, string $body): Pull`
  - [ ] `socket(string $host, int $port): Pull`
- [ ] All using `bracket()` for resource management
- [ ] Add network tests
- [ ] Add network examples

---

## Phase 4: Enhance Scope for Resource Management (MEDIUM PRIORITY)

### 4.1 Enhance current Scope class
**Status:** Basic Scope exists in `src/Type/Scope.php` but needs enhancement

- [ ] Add resource registration/tracking API
- [ ] Integrate with phunkie/effect's bracket
- [ ] Add resource lifecycle callbacks
- [ ] Implement finalization guarantees
- [ ] Add tests for scope resource management
- [ ] Document scope usage patterns

---

## Phase 5: Documentation & Examples (HIGH PRIORITY)

### 5.1 Update documentation to reflect phunkie/effect integration

- [ ] **README.md** updates:
  - [ ] Add section on resource management with bracket
  - [ ] Show error handling with attempt/handleError
  - [ ] Add flatMap examples for IO composition
  - [ ] Add badges (build status, code coverage, version)

- [ ] **doc/resource-streams.md** updates:
  - [ ] Show bracket usage patterns
  - [ ] Update all file I/O examples to use bracket
  - [ ] Add attempt/handleError examples
  - [ ] Remove references to unimplemented features

- [ ] **doc/advanced-topics.md** updates:
  - [ ] Document phunkie/effect integration
  - [ ] Show concurrency features (blocking, etc.)
  - [ ] Update error handling section
  - [ ] Add performance considerations

- [ ] **doc/core-concepts.md** updates:
  - [ ] Document IO from phunkie/effect
  - [ ] Explain bracket pattern
  - [ ] Update effect types section

### 5.2 Fix documentation gaps

- [ ] Audit all `.md` files for unimplemented features
- [ ] Either implement missing features OR remove from docs
- [ ] Ensure all code examples actually work
- [ ] Add "What's Implemented" section to README

### 5.3 Create new examples

- [ ] `examples/bracket-file.php` - File processing with bracket
- [ ] `examples/error-handling.php` - Error handling with attempt
- [ ] `examples/composition.php` - Stream composition with flatMap
- [ ] `examples/console-io.php` - Console I/O integration
- [ ] `examples/resource-management.php` - Complex resource scenarios

- [ ] Create `CONTRIBUTING.md` guide

---

## Phase 6: Advanced Features (LOWER PRIORITY)

### 6.1 Leverage phunkie/effect concurrency

- [ ] Investigate using `blocking()` for blocking I/O operations
- [ ] Explore ExecutionContext for async operations
- [ ] Implement parallel stream processing with ParallelOps
- [ ] Add backpressure mechanisms
- [ ] Add concurrency tests
- [ ] Document concurrency patterns

### 6.2 Performance optimizations

- [ ] Profile memory usage for large streams
- [ ] Optimize buffer sizes
- [ ] Improve chunk processing
- [ ] Add performance benchmarks
- [ ] Document performance best practices

---

## Phase 7: Testing & Quality (ONGOING)

### 7.1 Increase test coverage

- [ ] Bracket resource management tests
- [ ] Error handling with attempt/handleError tests
- [ ] Stream composition with flatMap tests
- [ ] Concurrent operations tests
- [ ] File I/O with proper cleanup tests
- [ ] Network operations tests (if implemented)
- [ ] Edge cases and error scenarios
- [ ] Performance tests for infinite streams

### 7.2 Code quality & tooling

- [ ] Setup PHP CS Fixer configuration
- [ ] Run PHPStan and fix issues
- [ ] Setup CI (Continuous Integration)
- [ ] Add code coverage reporting
- [ ] Document architectural decisions
- [ ] Enforce consistent coding style

---

## Release Management

- [ ] Define versioning strategy (SemVer)
- [ ] Create CHANGELOG.md
- [ ] Tag stable releases
- [ ] Publish to Packagist (already done)
- [ ] Document upgrade paths

---

## Immediate Next Steps (Priority Order)

1. ✅ **Phase 1 Complete** - Phunkie Effect Integration
2. **Import bracket** - Create `src/Functions/resource.php`
3. **Implement through()** - Essential for composability
4. **Implement chunk(), takeWhile(), dropWhile()** - Core operations
5. **Create file I/O functions** - `src/Functions/file.php`
6. **Update documentation** - Reflect current state and phunkie/effect usage
7. **Add comprehensive examples** - Show bracket, attempt, flatMap patterns
8. **Enhance Scope** - Better resource management
9. **Add tests** - Comprehensive coverage
10. **Code quality** - CS Fixer, PHPStan, CI

---

## Note on Phunkie Effect Changes

If changes to phunkie/effect are needed:
1. Create git worktree for parallel development
2. Clone phunkie/effect repository locally
3. Make changes and test locally
4. Submit PRs upstream to phunkie/effect
5. Use composer path repository for local testing

**Philosophy:** Leverage existing phunkie/effect functionality instead of reimplementing it.

---

## Progress Tracking

**Last Updated:** 2025-10-12
**Current Phase:** Phase 2 - Using Phunkie Effect Features
**Next Milestone:** Implement bracket integration and core stream operations
