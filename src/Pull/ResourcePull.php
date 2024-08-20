<?php

namespace Phunkie\Streams\Pull;

use Phunkie\Streams\Ops\Pull\CompileOps;
use Phunkie\Streams\Ops\Pull\ShowOps;
use Phunkie\Streams\Type\Pull;
use Phunkie\Streams\Type\Scope;
use Phunkie\Streams\Type\Stream;

class ResourcePull implements Pull
{
    use CompileOps;
    use ShowOps;
    private $resource;
    private $chunkSize;
    private $current;
    private $key;
    private Scope $scope;

    public function __construct($resource, int $chunkSize = 1024)
    {
        if (!is_resource($resource) || get_resource_type($resource) !== 'stream') {
            throw new \InvalidArgumentException("Invalid resource provided.");
        }

        $this->resource = $resource;
        $this->chunkSize = $chunkSize;
        $this->key = 0;
        $this->scope = new Scope();
    }

    public function pull()
    {
        return $this->current();
    }

    public function getScope(): Scope
    {
        return $this->scope;
    }

    public function getStream(): Stream
    {
        $stream = Stream($this->resource, $this->chunkSize);
        $stream->setScope($this->getScope());

        return $stream;
    }

    public function setScope(Scope $scope): static
    {
        $this->scope = $scope;

        return $this;
    }

    public function current()
    {
        return $this->current;
    }

    public function hasNext(): bool
    {
        return !feof($this->resource);
    }

    public function rewind(): void
    {
        if (is_resource($this->resource)) {
            rewind($this->resource);
        }
        $this->key = 0;
    }

    public function key(): int
    {
        return $this->key;
    }

    public function next(): void
    {
        if (!$this->hasNext()) {
            throw new \OutOfBoundsException("No more data to pull from the resource.");
        }

        $chunk = fread($this->resource, $this->chunkSize);

        if ($chunk === false) {
            throw new \RuntimeException("Error reading from resource.");
        }

        $this->current = $chunk;
        $this->key++;
    }

    public function valid(): bool
    {
        return $this->hasNext();
    }

    public function __destruct()
    {
        if (is_resource($this->resource)) {
            fclose($this->resource);
        }
    }
}