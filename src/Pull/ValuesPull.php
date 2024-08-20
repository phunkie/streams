<?php

namespace Phunkie\Streams\Pull;

use Phunkie\Streams\Ops\Pull\CompileOps;
use Phunkie\Streams\Ops\Pull\FunctorOps;
use Phunkie\Streams\Ops\Pull\ShowOps;
use Phunkie\Streams\Type\Pull;
use Phunkie\Streams\Type\Scope;
use Phunkie\Streams\Type\Stream;

class ValuesPull implements Pull
{
    use ShowOps;
    use CompileOps;
    use FunctorOps;

    private $values;
    private $index;
    private Scope $scope;

    /**
     * ValuesPull constructor.
     *
     * @param mixed ...$values The values to be pulled from.
     */
    public function __construct(...$values)
    {
        $this->values = $values;
        $this->index = 0;
        $this->scope = new Scope();
    }

    public function pull()
    {
        return $this->current();
    }

    public function getValues()
    {
        return $this->values;
    }

    public function getScope()
    {
        return $this->scope;
    }

    /**
     * Retrieves the next value in the sequence.
     *
     * @return void The next value.
     */
    public function next(): void
    {
        if (!$this->valid()) {
            throw new \OutOfBoundsException("No more elements to pull.");
        }

        $this->index++;
    }

    public function hasNext(): bool
    {
        return $this->index < count($this->values);
    }

    public function rewind(): void
    {
        $this->index = 0;
    }

    public function current(): mixed
    {
        return $this->values[$this->index];
    }

    public function key(): mixed
    {
        return $this->index;
    }

    public function valid(): bool
    {
        return $this->index < count($this->values);
    }

    public function toStream(): Stream
    {
        $stream = Stream(...$this->values);
        $stream->setScope($this->getScope());

        return $stream;
    }

    public function setScope(Scope $scope): static
    {
        $this->scope = $scope;

        return $this;
    }
}