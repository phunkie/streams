<?php

namespace Phunkie\Streams\Pull;

use Phunkie\Streams\Type\Pull;
use Phunkie\Streams\Type\Scope;
use Phunkie\Streams\Type\Stream;
use Phunkie\Types\ImmList;

class ResourcePullConcat implements Pull
{
    private $pull1;
    private $pull2;
    private $currentPull;
    private Scope $scope;

    public function __construct(ResourcePull $pull1, ResourcePull $pull2)
    {
        $this->pull1 = $pull1;
        $this->pull2 = $pull2;
        $this->currentPull = $this->pull1;
        $this->scope = new Scope();
    }

    public function pull()
    {
        try {
            return $this->currentPull->pull();
        } catch (\OutOfBoundsException $e) {
            if ($this->currentPull === $this->pull1) {
                $this->currentPull = $this->pull2;
                return $this->currentPull->pull();
            } else {
                throw new \OutOfBoundsException("No more data to pull from the resource.");
            }
        }
    }

    public function hasNext(): bool
    {
        return $this->currentPull->hasNext() || ($this->currentPull === $this->pull1 && $this->pull2->hasNext());
    }

    public function rewind(): void
    {
        $this->pull1->rewind();
        $this->pull2->rewind();
        $this->currentPull = $this->pull1;
    }

    public function show(): string
    {
        return $this->pull1->show() . " ++ " . $this->pull2->show();
    }

    public function getScope(): Scope
    {
        return $this->scope;
    }

    public function setScope(Scope $scope): static
    {
        $this->scope = $scope;

        return $this;
    }

    public function toStream(): Stream
    {
        $stream = Stream($this->pull1, $this->pull2);
        $stream->setScope($this->getScope());

        return $stream;
    }
}