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
use Phunkie\Streams\Ops\Pull\ResourcePull\CompileOps;
use Phunkie\Streams\Ops\Pull\ResourcePull\FunctorOps;
use Phunkie\Streams\Ops\Pull\ResourcePull\ShowOps;
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
    use FunctorOps;
    use ShowOps;

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
        // Resource objects typically can't rewind
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
     * Consumes the entire resource, applying scope maps and filters to each chunk.
     *
     * @return array<mixed> All transformed and filtered chunks.
     */
    public function getValues(): array
    {
        $values = [];

        while ($this->hasNext()) {
            $this->next();
            if ($this->current !== null) {
                $value = $this->current;

                // Apply maps from scope
                foreach ($this->getScope()->getMaps() as $f) {
                    $value = $f($value);
                }

                // Apply filters from scope
                $passesFilters = true;
                foreach ($this->getScope()->getFilters() as $filter) {
                    if (!$filter($value)) {
                        $passesFilters = false;

                        break;
                    }
                }

                if ($passesFilters) {
                    $values[] = $value;
                }
            }
        }

        return $values;
    }

    /**
     * Registers a mapping function to be applied on getValues().
     *
     * @param callable $f The mapping function.
     * @return static
     */
    public function map($f): static
    {
        $this->getScope()->addMap($f);

        return $this;
    }

    /**
     * Registers a filter predicate to be applied on getValues().
     *
     * @param callable $f The filter predicate.
     * @return static
     */
    public function filter(callable $f): static
    {
        $this->getScope()->addFilter($f);

        return $this;
    }
}
