<?php

/**
 * Network Operations Examples
 *
 * This file demonstrates network operations including HTTP requests and TCP sockets.
 * Note: Some examples require external services or may be commented out for safety.
 */

use Phunkie\Streams\IO\Network\SocketAddress;
use Phunkie\Streams\Network;
use Phunkie\Streams\Type\Stream;

require_once dirname(__FILE__, 2) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/printLn.php';

echo "=== Network Operations Examples ===\n\n";

// Example 1: HTTP GET request (using httpbin.org for testing)
echo "1. HTTP GET request:\n";

try {
    $response = Network::httpGet('http://httpbin.org/get')
        ->compile->toArray();

    $fullResponse = implode('', $response);
    $preview = substr($fullResponse, 0, 200);
    echo "   Response preview: " . $preview . "...\n";
} catch (\Exception $e) {
    echo "   [SKIP] Could not connect to httpbin.org: " . $e->getMessage() . "\n";
}
echo "\n";

// Example 2: HTTP POST request with JSON
echo "2. HTTP POST request with JSON:\n";

try {
    $payload = json_encode(['name' => 'Alice', 'age' => 30]);
    $headers = ['Content-Type: application/json'];

    $response = Network::httpPost('http://httpbin.org/post', $payload, $headers)
        ->compile->toArray();

    $fullResponse = implode('', $response);
    if (strpos($fullResponse, 'Alice') !== false) {
        echo "   ✓ POST successful, data echoed back\n";
    } else {
        echo "   Response received\n";
    }
} catch (\Exception $e) {
    echo "   [SKIP] Could not connect to httpbin.org: " . $e->getMessage() . "\n";
}
echo "\n";

// Example 3: HTTP GET with stream processing
echo "3. HTTP GET with stream processing:\n";

try {
    $chunkCount = Network::httpGet('http://httpbin.org/stream/5')
        ->map(fn ($chunk) => strlen($chunk))
        ->compile->toArray();

    echo "   Received " . count($chunkCount) . " chunks\n";
    echo "   Total bytes: " . array_sum($chunkCount) . "\n";
} catch (\Exception $e) {
    echo "   [SKIP] Could not connect to httpbin.org: " . $e->getMessage() . "\n";
}
echo "\n";

// Example 4: TCP Echo Server (demonstration - commented out)
echo "4. TCP Echo Server (demonstration code):\n";
echo "   ```php\n";
echo "   Network::server(host: 'localhost', port: 8080)\n";
echo "       ->map(function(\$client) {\n";
echo "           \$data = fread(\$client, 1024);\n";
echo "           fwrite(\$client, \"Echo: \$data\");\n";
echo "           fclose(\$client);\n";
echo "           return \"Handled client\";\n";
echo "       })\n";
echo "       ->take(10)\n";
echo "       ->compile->drain\n";
echo "       ->unsafeRunSync();\n";
echo "   ```\n";
echo "   [This would start a server on localhost:8080]\n\n";

// Example 5: TCP Client (demonstration - commented out)
echo "5. TCP Client (demonstration code):\n";
echo "   ```php\n";
echo "   Network::client(new SocketAddress('localhost', 8080))\n";
echo "       ->map(fn(\$data) => \"Received: \$data\")\n";
echo "       ->compile->toArray()\n";
echo "       ->unsafeRunSync();\n";
echo "   ```\n";
echo "   [This would connect to a server on localhost:8080]\n\n";

// Example 6: Socket write pipe
echo "6. Socket write pipe (demonstration code):\n";
echo "   ```php\n";
echo "   Stream(...['message1', 'message2', 'message3'])\n";
echo "       ->through(Network::socketWrite(\n";
echo "           new SocketAddress('localhost', 8080)\n";
echo "       ));\n";
echo "   ```\n";
echo "   [This would send messages to a server]\n\n";

// Example 7: SocketAddress usage
echo "7. Creating SocketAddress:\n";
$addr = new SocketAddress('localhost', 8080);
echo "   Address: " . $addr->toString() . "\n";
echo "   Host: " . $addr->getHost() . "\n";
echo "   Port: " . $addr->getPort() . "\n\n";

// Example 8: HTTP with error handling
echo "8. HTTP with error handling:\n";
$url = 'http://httpbin.org/status/404';

try {
    $response = Network::httpGet($url)
        ->compile->toArray();

    $fullResponse = implode('', $response);
    echo "   Received response (even for 404): " . strlen($fullResponse) . " bytes\n";
} catch (\Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Example 9: HTTP PUT request
echo "9. HTTP PUT request (demonstration):\n";
echo "   ```php\n";
echo "   Network::httpPut(\n";
echo "       'https://api.example.com/users/1',\n";
echo "       json_encode(['name' => 'Alice Updated']),\n";
echo "       ['Content-Type: application/json']\n";
echo "   )->compile->toArray()->unsafeRunSync();\n";
echo "   ```\n\n";

// Example 10: HTTP DELETE request
echo "10. HTTP DELETE request (demonstration):\n";
echo "   ```php\n";
echo "   Network::httpDelete(\n";
echo "       'https://api.example.com/users/1',\n";
echo "       ['Authorization: Bearer token123']\n";
echo "   )->compile->drain->unsafeRunSync();\n";
echo "   ```\n\n";

// Example 11: Processing JSON API response
echo "11. Processing JSON API response:\n";

try {
    $users = Network::httpGet('http://httpbin.org/json')
        ->map(fn ($chunk) => $chunk) // Could parse JSON here
        ->compile->toArray();

    $json = implode('', $users);
    $data = json_decode($json, true);

    if ($data) {
        echo "   ✓ Successfully parsed JSON response\n";
        echo "   Keys: " . implode(', ', array_keys($data)) . "\n";
    }
} catch (\Exception $e) {
    echo "   [SKIP] Could not connect: " . $e->getMessage() . "\n";
}
echo "\n";

// Example 12: Real-world scenario - API client with transformation
echo "12. API client with data transformation:\n";

try {
    $transformed = Network::httpGet('http://httpbin.org/uuid')
        ->map(fn ($chunk) => json_decode($chunk, true))
        ->filter(fn ($data) => $data !== null)
        ->map(fn ($data) => $data['uuid'] ?? 'N/A')
        ->compile->toArray();

    if (!empty($transformed)) {
        echo "   ✓ Received and transformed data\n";
    }
} catch (\Exception $e) {
    echo "   [SKIP] Could not connect: " . $e->getMessage() . "\n";
}
echo "\n";

// Example 13: Chaining HTTP requests (demonstration)
echo "13. Chaining HTTP requests (demonstration code):\n";
echo "   ```php\n";
echo "   Network::httpGet('https://api.example.com/users/1')\n";
echo "       ->map(fn(\$data) => json_decode(\$data, true))\n";
echo "       ->flatMap(fn(\$user) => \n";
echo "           Network::httpGet(\"https://api.example.com/users/{\$user['id']}/posts\")\n";
echo "       )\n";
echo "       ->compile->toArray()\n";
echo "       ->unsafeRunSync();\n";
echo "   ```\n\n";

// Example 14: Building a simple HTTP client wrapper
echo "14. Building a reusable API client:\n";
$apiClient = function (string $endpoint) {
    $baseUrl = 'http://httpbin.org';
    $chunks = Network::httpGet($baseUrl . $endpoint)
        ->map(fn ($chunk) => $chunk)
        ->compile->toArray();

    return implode('', $chunks);
};

try {
    $result = $apiClient('/headers');
    echo "   ✓ API client wrapper working\n";
} catch (\Exception $e) {
    echo "   [SKIP] Could not connect: " . $e->getMessage() . "\n";
}
echo "\n";

// Example 15: Concurrent server pattern (demonstration)
echo "15. Concurrent server pattern (demonstration code):\n";
echo "   ```php\n";
echo "   // Echo server that handles multiple clients\n";
echo "   Network::server(host: '0.0.0.0', port: 8080)\n";
echo "       ->map(function(\$clientSocket) {\n";
echo "           // Read request\n";
echo "           \$request = '';\n";
echo "           while (!\feof(\$clientSocket)) {\n";
echo "               \$chunk = fread(\$clientSocket, 1024);\n";
echo "               if (\$chunk === '') break;\n";
echo "               \$request .= \$chunk;\n";
echo "               if (strpos(\$request, \"\\n\\n\") !== false) break;\n";
echo "           }\n";
echo "           \n";
echo "           // Send response\n";
echo "           fwrite(\$clientSocket, \"HTTP/1.1 200 OK\\r\\n\");\n";
echo "           fwrite(\$clientSocket, \"Content-Type: text/plain\\r\\n\\r\\n\");\n";
echo "           fwrite(\$clientSocket, \"Echo: \$request\");\n";
echo "           fclose(\$clientSocket);\n";
echo "           \n";
echo "           return 'Client handled';\n";
echo "       })\n";
echo "       ->compile->drain\n";
echo "       ->unsafeRunSync();\n";
echo "   ```\n\n";

echo "=== All network examples completed! ===\n";
echo "\nNote: Some examples use httpbin.org for testing HTTP operations.\n";
echo "Socket examples are shown as demonstration code to avoid binding ports.\n";
