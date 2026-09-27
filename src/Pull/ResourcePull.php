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

use Phunkie\Streams\Ops\Pull\CompileOps;
use Phunkie\Streams\Ops\Pull\EffectfulOps;
use Phunkie\Streams\Ops\Pull\FunctorOps;
use Phunkie\Streams\Ops\Pull\ImmListOps;
use Phunkie\Streams\Ops\Pull\MonadOps;
use Phunkie\Streams\Ops\Pull\ResourcePull\LogOps;
use Phunkie\Streams\Ops\Pull\ResourcePull\ShowOps;
use Phunkie\Streams\Ops\Pull\TextOps;
use Phunkie\Streams\Ops\Pull\TransformationOps;
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
    use LogOps;
    use EffectfulOps;
    use FunctorOps;
    use ImmListOps;
    use MonadOps;
    use ShowOps;
    use TextOps;
    use TransformationOps;

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
    public function pull(): mixed
    {
        return $this->current();
    }

    /**
     * Yields the chunks one read at a time, from the handle's current position to its end.
     *
     * @return \Generator<int, string>
     */
    public function elements(): \Generator
    {
        while ($this->hasNext()) {
            $this->next();
            if ($this->current !== null) {
                yield $this->current;
            }
        }
    }

    /**
     * Returns the current scope.
     *
     * @return Scope
     */
    public function getScope(): Scope
    {
        return $this->scope;
    }

    /**
     * Converts this pull into a Stream, preserving the current scope.
     *
     * @return Stream
     */
    public function toStream(): Stream
    {
        $stream = Stream($this->resource, $this->chunkSize);
        $stream->setScope($this->getScope());

        return $stream;
    }

    /**
     * Replace the current scope.
     *
     * @param Scope $scope The new scope.
     * @return static
     */
    public function setScope(Scope $scope): static
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * Set the number of bytes read on each pull.
     *
     * @param int $chunkSize Bytes to read per iteration.
     * @return static
     */
    public function setChunkSize(int $chunkSize): static
    {
        $this->chunkSize = $chunkSize;

        return $this;
    }

    /**
     * Returns the last-read chunk.
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->current;
    }

    /**
     * Checks whether the underlying resource has more data.
     *
     * @return bool
     */
    public function hasNext(): bool
    {
        return !feof($this->resource);
    }

    /**
     * Rewinds the resource pointer and resets the key counter.
     *
     * @return void
     */
    public function rewind(): void
    {
        if (is_resource($this->resource)) {
            rewind($this->resource);
        }
        $this->key = 0;
    }

    /**
     * Returns the current chunk index.
     *
     * @return int
     */
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

        if ($chunk === '') {
            $this->current = null;

            return;
        }

        $this->current = $chunk;
        $this->key++;
    }

    /**
     * Returns true while the resource has not reached EOF.
     *
     * @return bool
     */
    public function valid(): bool
    {
        return $this->hasNext();
    }

    /**
     * Consumes the rest of the resource and returns its chunks as an array.
     *
     * @return array
     */
    public function getValues(): array
    {
        return iterator_to_array($this->elements(), false);
    }

    /** Closes the underlying resource handle. */
    public function __destruct()
    {
        if (is_resource($this->resource)) {
            fclose($this->resource);
        }
    }
}
