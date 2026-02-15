<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams\Pull;

use Phunkie\Streams\Ops\Pull\ResourcePull\CompileOps;
use Phunkie\Streams\Ops\Pull\ResourcePull\ShowOps;
use Phunkie\Streams\Type\Pull;
use Phunkie\Streams\Type\Scope;
use Phunkie\Streams\Type\Stream;

/**
 * Pull backed by a raw PHP stream resource (file handles, php://memory, etc.).
 *
 * Reads data in fixed-size chunks via fread(). The resource is closed
 * automatically when this object is destroyed.
 */
class ResourcePull implements Pull
{
    use CompileOps;
    use ShowOps;
    private $resource;
    private $chunkSize;
    private $current;
    private $key;
    private Scope $scope;

    /**
     * @param resource $resource  A PHP stream resource (e.g. from fopen()).
     * @param int      $chunkSize Bytes to read per iteration.
     *
     * @throws \InvalidArgumentException If $resource is not a valid stream resource.
     */
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

    /**
     * Returns the current chunk without advancing the iterator.
     *
     * Call next() first to populate the current value after construction.
     *
     * @return string|null The current chunk, or null before the first next().
     */
    public function pull()
    {
        return $this->current();
    }

    /** Returns the current scope. */
    public function getScope(): Scope
    {
        return $this->scope;
    }

    /** Converts this pull into a Stream, preserving the current scope. */
    public function toStream(): Stream
    {
        $stream = Stream($this->resource, $this->chunkSize);
        $stream->setScope($this->getScope());

        return $stream;
    }

    /** Replace the current scope. */
    public function setScope(Scope $scope): static
    {
        $this->scope = $scope;

        return $this;
    }

    /** Returns the last-read chunk. */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->current;
    }

    /** Checks whether the underlying resource has more data. */
    public function hasNext(): bool
    {
        return !feof($this->resource);
    }

    /** Rewinds the resource pointer and resets the key counter. */
    public function rewind(): void
    {
        if (is_resource($this->resource)) {
            rewind($this->resource);
        }
        $this->key = 0;
    }

    /** Returns the current chunk index. */
    public function key(): int
    {
        return $this->key;
    }

    /**
     * Reads the next chunk from the resource.
     *
     * @throws \OutOfBoundsException If no more data is available.
     * @throws \RuntimeException     If fread() fails.
     */
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

    /** Returns true while the resource has not reached EOF. */
    public function valid(): bool
    {
        return $this->hasNext();
    }

    /**
     * Consumes the entire resource and returns all chunks as an array.
     *
     * Rewinds the resource before reading.
     */
    public function getValues(): array
    {
        $values = [];
        $this->rewind();

        while ($this->hasNext()) {
            $this->next();
            if ($this->current !== null) {
                $values[] = $this->current;
            }
        }

        return $values;
    }

    /** Closes the underlying resource handle. */
    public function __destruct()
    {
        if (is_resource($this->resource)) {
            fclose($this->resource);
        }
    }
}
