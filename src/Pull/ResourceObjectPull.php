<?php

namespace Phunkie\Streams\Pull;

use Phunkie\Streams\IO\Resource;
use Phunkie\Streams\Ops\Pull\ResourcePull\CompileOps;
use Phunkie\Streams\Ops\Pull\ResourcePull\FunctorOps;
use Phunkie\Streams\Ops\Pull\ResourcePull\ShowOps;
use Phunkie\Streams\Type\Pull;
use Phunkie\Streams\Type\Scope;
use Phunkie\Streams\Type\Stream;

/**
 * Pull for Resource objects (like SocketRead, HttpRequest, etc.)
 * Unlike ResourcePull which expects a raw stream resource,
 * this works with Resource interface implementations
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

    public function __construct(
        private Resource $resource,
        private int $chunkSize = 4096
    ) {
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

    public function toStream(): Stream
    {
        return Stream::fromResourceObject($this->resource);
    }

    public function setScope(Scope $scope): static
    {
        $this->scope = $scope;
        return $this;
    }

    public function current(): mixed
    {
        return $this->current;
    }

    public function hasNext(): bool
    {
        return !$this->eof;
    }

    public function rewind(): void
    {
        // Resource objects typically can't rewind
        $this->key = 0;
    }

    public function key(): int
    {
        return $this->key;
    }

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

    public function valid(): bool
    {
        return $this->hasNext();
    }

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

    public function map($f): static
    {
        $this->getScope()->addMap($f);
        return $this;
    }

    public function filter(callable $f): static
    {
        $this->getScope()->addFilter($f);
        return $this;
    }
}
