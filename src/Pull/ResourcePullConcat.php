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

use Phunkie\Streams\Ops\Pull\ResourcePullConcat\CompileOps;
use Phunkie\Streams\Ops\Pull\ResourcePullConcat\ShowOps;
use Phunkie\Streams\Type\Pull;
use Phunkie\Streams\Type\Scope;
use Phunkie\Streams\Type\Stream;

class ResourcePullConcat implements Pull
{
    use CompileOps;
    use ShowOps;

    private ResourcePull $pull1;
    private ResourcePull $pull2;
    private ResourcePull $currentPull;
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

    public function getPull1(): ResourcePull
    {
        return $this->pull1;
    }

    public function getPull2(): ResourcePull
    {
        return $this->pull2;
    }

    public function current(): mixed
    {
        return $this->currentPull->current();
    }

    public function key(): mixed
    {
        return $this->currentPull->key();
    }

    public function next(): void
    {
        try {
            $this->currentPull->next();
        } catch (\OutOfBoundsException $e) {
            if ($this->currentPull === $this->pull1 && $this->pull2->hasNext()) {
                $this->currentPull = $this->pull2;
                $this->currentPull->next();
            } else {
                throw $e;
            }
        }
    }

    public function valid(): bool
    {
        return $this->hasNext();
    }

    public function getValues(): array
    {
        $values = [];
        $this->rewind();

        while ($this->hasNext()) {
            try {
                $this->next();
                $values[] = $this->current();
            } catch (\OutOfBoundsException $e) {
                break;
            }
        }

        return $values;
    }
}
