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

use Phunkie\Streams\IO\Resource;
use Phunkie\Streams\Ops\Pull\CompileOps;
use Phunkie\Streams\Ops\Pull\EffectfulOps;
use Phunkie\Streams\Ops\Pull\FunctorOps;
use Phunkie\Streams\Ops\Pull\ImmListOps;
use Phunkie\Streams\Ops\Pull\MonadOps;
use Phunkie\Streams\Ops\Pull\ResourcePull\LogOps;
use Phunkie\Streams\Ops\Pull\ResourcePull\ShowOps;
use Phunkie\Streams\Ops\Pull\TransformationOps;
use Phunkie\Streams\Type\Pull;
use Phunkie\Streams\Type\Scope;
use Phunkie\Streams\Type\Stream;

/**
 * Pull backed by a Resource interface object (SocketRead, HttpRequest, etc.).
 *
 * Unlike ResourcePull which wraps a raw PHP stream resource, this class
 * works with Resource interface implementations that define their own
 * pull($chunkSize) method and EOF sentinel.
 */
class ResourceObjectPull implements Pull
{
    use CompileOps;
    use LogOps;
    use EffectfulOps;
    use FunctorOps;
    use ImmListOps;
    use MonadOps;
    use ShowOps;
    use TransformationOps;

    private $current;
    private int $key;
    private Scope $scope;
    private bool $eof = false;

    /**
     * @param Resource $resource  The Resource implementation to read from.
     * @param int      $chunkSize Bytes to request per pull.
     */
    public function __construct(
        private Resource $resource,
        private int $chunkSize = 4096
    ) {
        $this->key = 0;
        $this->scope = new Scope();
    }

    /**
     * Returns the current chunk without advancing.
     *
     * @return mixed The current chunk, or null if EOF reached or before first next().
     */
    public function pull(): mixed
    {
        return $this->current();
    }

    /**
     * Yields the chunks one pull at a time until the resource reports EOF.
     *
     * @return \Generator<int, mixed>
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
     * Converts this pull into a Stream backed by the same Resource object.
     *
     * @return Stream
     */
    public function toStream(): Stream
    {
        return Stream::fromResourceObject($this->resource);
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
     * Set the number of bytes requested on each pull.
     *
     * @param int $chunkSize Bytes to request per pull.
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
    public function current(): mixed
    {
        return $this->current;
    }

    /**
     * Returns true while EOF has not been reached.
     *
     * @return bool
     */
    public function hasNext(): bool
    {
        return !$this->eof;
    }

    /**
     * Resets the key counter. Resource objects typically cannot rewind.
     *
     * @return void
     */
    public function rewind(): void
    {
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
     * Pulls the next chunk from the Resource object.
     *
     * Sets EOF when Resource::EOF is returned.
     *
     * @throws \OutOfBoundsException If already at EOF.
     */
    public function next(): void
    {
        if ($this->eof) {
            throw new \OutOfBoundsException("No more data to pull from the resource.");
        }

        $chunk = $this->resource->pull($this->chunkSize);

        if ($chunk === Resource::EOF) {
            $this->eof = true;
            $this->current = null;
        } else {
            $this->current = $chunk;
            $this->key++;
        }
    }

    /**
     * Returns true while EOF has not been reached.
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
     * @return array<mixed>
     */
    public function getValues(): array
    {
        return iterator_to_array($this->elements(), false);
    }
}
