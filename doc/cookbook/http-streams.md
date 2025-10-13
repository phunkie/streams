# HTTP Streams

This section provides recipes for working with HTTP streams using Phunkie Streams' Network API.

## HTTP Request/Response

### Basic HTTP GET Request

**Problem**: Fetch data from an API endpoint.

**Solution**:
```php
<?php
use Phunkie\Streams\Network;

$response = Network::httpGet('https://api.example.com/data')
    ->compile->toArray();

$body = implode('', $response);
$data = json_decode($body, true);
```

**Discussion**: `Network::httpGet()` creates a stream of response chunks. Resources are automatically cleaned up via `__destruct()`.

### HTTP POST Request

**Problem**: Send data to an API endpoint.

**Solution**:
```php
<?php
use Phunkie\Streams\Network;

$payload = json_encode(['name' => 'Alice', 'email' => 'alice@example.com']);

$response = Network::httpPost(
    'https://api.example.com/users',
    $payload,
    ['Content-Type: application/json']
)->compile->toArray();

$body = implode('', $response);
$result = json_decode($body, true);
```

**Discussion**: `Network::httpPost()` accepts URL, body, and optional headers array.

### Other HTTP Methods

**Problem**: Use PUT, DELETE, or other HTTP methods.

**Solution**:
```php
<?php
use Phunkie\Streams\Network;

// PUT request
$response = Network::httpPut(
    'https://api.example.com/users/123',
    json_encode(['name' => 'Bob']),
    ['Content-Type: application/json']
)->compile->toArray();

// DELETE request
$response = Network::httpDelete(
    'https://api.example.com/users/123',
    ['Authorization: Bearer token123']
)->compile->toArray();
```

**Discussion**: The Network API provides methods for all common HTTP verbs.

## Processing JSON APIs

### Fetching and Parsing JSON

**Problem**: Fetch JSON data from an API and parse it.

**Solution**:
```php
<?php
use Phunkie\Streams\Network;

$users = Network::httpGet('https://api.example.com/users')
    ->map(fn($chunk) => json_decode($chunk, true))
    ->filter(fn($data) => $data !== null)
    ->compile->toArray();
```

**Discussion**: Process JSON responses by mapping over chunks and filtering nulls.

### Streaming JSON Lines

**Problem**: Process a JSON Lines (JSONL) API response.

**Solution**:
```php
<?php
use Phunkie\Streams\Network;

$records = Network::httpGet('https://api.example.com/export')
    ->map(fn($line) => json_decode($line, true))
    ->filter(fn($data) => $data !== null)
    ->filter(fn($data) => $data['active'] === true)
    ->map(fn($data) => [
        'id' => $data['id'],
        'name' => $data['name']
    ])
    ->compile->toArray();
```

**Discussion**: JSON Lines format allows streaming large datasets line by line.

### Aggregating API Data

**Problem**: Fetch data from multiple endpoints and combine results.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use Phunkie\Streams\Network;

$endpoints = ['/users', '/products', '/orders'];

$allData = Stream(...$endpoints)
    ->map(fn($endpoint) =>
        Network::httpGet("https://api.example.com$endpoint")
            ->compile->toArray()
    )
    ->map(fn($chunks) => implode('', $chunks))
    ->map(fn($body) => json_decode($body, true))
    ->toArray();
```

**Discussion**: Use Stream to iterate over endpoints and collect all responses.

## Error Handling

### Handling HTTP Errors

**Problem**: Gracefully handle HTTP errors and network failures.

**Solution**:
```php
<?php
use Phunkie\Streams\Network;

$data = Network::httpGet('https://api.example.com/data')
    ->map(fn($chunk) => json_decode($chunk, true))
    ->attempt()
    ->unsafeRunSync()
    ->getOrElse([]);  // Default empty array on error
```

**Discussion**: Use `attempt()` to convert exceptions to Validation for safe error handling.

### Retry Logic

**Problem**: Retry failed HTTP requests.

**Solution**:
```php
<?php
use Phunkie\Streams\Network;
use function Phunkie\Effect\Functions\io\io;

$fetchWithRetry = function(string $url, int $maxAttempts = 3) {
    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        $result = Network::httpGet($url)
            ->map(fn($chunk) => $chunk)
            ->attempt()
            ->unsafeRunSync();

        if ($result->isSuccess()) {
            return io(fn() => $result->getOrElse([]));
        }

        if ($attempt < $maxAttempts) {
            sleep(pow(2, $attempt)); // Exponential backoff
        }
    }

    return io(fn() => throw new \RuntimeException("Failed after $maxAttempts attempts"));
};

$data = $fetchWithRetry('https://api.example.com/data')
    ->unsafeRunSync();
```

**Discussion**: Implement exponential backoff for resilient API clients.

### Fallback Data Sources

**Problem**: Try multiple API endpoints with fallback.

**Solution**:
```php
<?php
use Phunkie\Streams\Network;

$tryEndpoints = function(array $urls) {
    foreach ($urls as $url) {
        $result = Network::httpGet($url)
            ->map(fn($chunk) => json_decode($chunk, true))
            ->attempt()
            ->unsafeRunSync();

        if ($result->isSuccess()) {
            return $result->getOrElse([]);
        }
    }

    throw new \RuntimeException("All endpoints failed");
};

$data = $tryEndpoints([
    'https://api-primary.example.com/data',
    'https://api-backup.example.com/data',
    'https://api-fallback.example.com/data'
]);
```

**Discussion**: Iterate through endpoints until one succeeds.

## REST API Client Pattern

### Creating a Reusable API Client

**Problem**: Build a reusable REST API client.

**Solution**:
```php
<?php
use Phunkie\Streams\Network;

class ApiClient
{
    private string $baseUrl;
    private array $defaultHeaders;

    public function __construct(string $baseUrl, array $defaultHeaders = [])
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->defaultHeaders = $defaultHeaders;
    }

    public function get(string $endpoint): array
    {
        return Network::httpGet(
            $this->baseUrl . $endpoint,
            $this->defaultHeaders
        )
            ->compile->toArray();
    }

    public function post(string $endpoint, array $data): array
    {
        return Network::httpPost(
            $this->baseUrl . $endpoint,
            json_encode($data),
            array_merge($this->defaultHeaders, ['Content-Type: application/json'])
        )
            ->compile->toArray();
    }

    public function getJson(string $endpoint): ?array
    {
        $chunks = $this->get($endpoint);
        $body = implode('', $chunks);
        return json_decode($body, true);
    }

    public function postJson(string $endpoint, array $data): ?array
    {
        $chunks = $this->post($endpoint, $data);
        $body = implode('', $chunks);
        return json_decode($body, true);
    }
}

// Usage
$client = new ApiClient('https://api.example.com', [
    'Authorization: Bearer mytoken123'
]);

$users = $client->getJson('/users');
$newUser = $client->postJson('/users', ['name' => 'Alice']);
```

**Discussion**: Encapsulate common API operations in a reusable client class.

## Advanced Patterns

### Pagination Handling

**Problem**: Fetch all pages from a paginated API.

**Solution**:
```php
<?php
use Phunkie\Streams\Network;

$fetchAllPages = function(string $baseUrl) {
    $allData = [];
    $page = 1;
    $hasMore = true;

    while ($hasMore) {
        $response = Network::httpGet("$baseUrl?page=$page")
            ->compile->toArray();

        $body = implode('', $response);
        $data = json_decode($body, true);

        if (empty($data['items'])) {
            $hasMore = false;
        } else {
            $allData = array_merge($allData, $data['items']);
            $page++;

            if (!isset($data['hasMore']) || !$data['hasMore']) {
                $hasMore = false;
            }
        }
    }

    return $allData;
};

$allUsers = $fetchAllPages('https://api.example.com/users');
```

**Discussion**: Loop through pages until no more data is available.

### Rate Limiting

**Problem**: Respect API rate limits.

**Solution**:
```php
<?php
use function Phunkie\Streams\Stream;
use Phunkie\Streams\Network;

class RateLimitedClient
{
    private int $requestsPerSecond;
    private float $lastRequestTime = 0;

    public function __construct(int $requestsPerSecond = 10)
    {
        $this->requestsPerSecond = $requestsPerSecond;
    }

    public function get(string $url): array
    {
        $this->waitIfNeeded();

        $response = Network::httpGet($url)
            ->compile->toArray();

        $this->lastRequestTime = microtime(true);
        return $response;
    }

    private function waitIfNeeded(): void
    {
        $minInterval = 1.0 / $this->requestsPerSecond;
        $elapsed = microtime(true) - $this->lastRequestTime;

        if ($elapsed < $minInterval) {
            usleep((int)(($minInterval - $elapsed) * 1000000));
        }
    }
}

$client = new RateLimitedClient(10); // 10 requests per second

$endpoints = ['/users/1', '/users/2', '/users/3'];
$results = Stream(...$endpoints)
    ->map(fn($endpoint) => $client->get("https://api.example.com$endpoint"))
    ->toArray();
```

**Discussion**: Throttle requests to respect API rate limits.

### Caching Responses

**Problem**: Cache API responses to reduce load.

**Solution**:
```php
<?php
use Phunkie\Streams\Network;
use function Phunkie\Streams\IO\File\{writeFileContents, readFileContents, exists};
use Phunkie\Streams\IO\File\Path;

class CachedApiClient
{
    private string $cacheDir;
    private int $cacheTtl;

    public function __construct(string $cacheDir, int $cacheTtl = 3600)
    {
        $this->cacheDir = $cacheDir;
        $this->cacheTtl = $cacheTtl;
    }

    public function get(string $url): string
    {
        $cacheKey = md5($url);
        $cachePath = new Path("{$this->cacheDir}/$cacheKey");

        // Check cache
        $fileExists = exists($cachePath)->unsafeRunSync();
        if ($fileExists && (time() - filemtime($cachePath->toString()) < $this->cacheTtl)) {
            return readFileContents($cachePath)->unsafeRunSync();
        }

        // Fetch from API
        $chunks = Network::httpGet($url)->compile->toArray();
        $body = implode('', $chunks);

        // Cache the response
        writeFileContents($cachePath, $body)->unsafeRunSync();

        return $body;
    }
}

$client = new CachedApiClient('/tmp/api-cache', 3600);
$data = json_decode($client->get('https://api.example.com/data'), true);
```

**Discussion**: Combine file I/O with HTTP requests for simple caching.

## Best Practices

1. **Resource Management**
   - Network resources clean up automatically via `__destruct()`
   - No need for manual bracket - trust automatic cleanup
   - Streams handle connection lifecycle

2. **Error Handling**
   - Use `attempt()` for operations that may fail
   - Implement retry logic with exponential backoff
   - Provide fallback data sources
   - Use `getOrElse()` for default values

3. **Performance**
   - Process responses as streams when possible
   - Use chunked processing for large responses
   - Implement caching for frequently accessed data
   - Respect API rate limits

4. **Security**
   - Always use HTTPS for sensitive data
   - Store API keys in environment variables
   - Validate and sanitize API responses
   - Implement proper authentication headers

5. **API Design**
   - Create reusable API client classes
   - Encapsulate common patterns (pagination, retries)
   - Use type hints and return types
   - Document API requirements and limitations

## See Also

- [Network Operations](../getting-started.md#working-with-network-resources) - Network basics
- [Resource Management](../resource-management.md) - Resource patterns
- [Error Handling](../error-handling.md) - Comprehensive error handling
- [Resource Streams](../resource-streams.md) - Deep dive into network I/O
