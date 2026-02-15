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

/**
 * Concatenation of two ResourcePulls into a single Pull.
 *
 * Drains the first ResourcePull completely before switching to the second.
 * Delegates all Iterator operations to the currently active pull.
 */
class ResourcePullConcat implements Pull
{
    use CompileOps;
    use ShowOps;

    private ResourcePull $pull1;
    private ResourcePull $pull2;
    private ResourcePull $currentPull;
    private Scope $scope;

    /**
     * @param ResourcePull $pull1 The first pull to drain.
     * @param ResourcePull $pull2 The second pull, consumed after $pull1 is exhausted.
     */
    public function __construct(ResourcePull $pull1, ResourcePull $pull2)
    {
        $this->pull1 = $pull1;
        $this->pull2 = $pull2;
        $this->currentPull = $this->pull1;
        $this->scope = new Scope();
    }

    /**
     * Pulls the current value, switching to pull2 when pull1 is exhausted.
     *
     * @return mixed The current chunk from the active pull.
     *
     * @throws \OutOfBoundsException If both pulls are exhausted.
     */
    public function pull(): mixed
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

    /**
     * Returns true if either pull still has data.
     *
     * @return bool
     */
    public function hasNext(): bool
    {
        return $this->currentPull->hasNext() || ($this->currentPull === $this->pull1 && $this->pull2->hasNext());
    }

    /**
     * Rewinds both underlying pulls and resets to pull1.
     *
     * @return void
     */
    public function rewind(): void
    {
        $this->pull1->rewind();
        $this->pull2->rewind();
        $this->currentPull = $this->pull1;
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
     * Converts this concatenated pull into a Stream, preserving the current scope.
     *
     * @return Stream
     */
    public function toStream(): Stream
    {
        $stream = Stream($this->pull1, $this->pull2);
        $stream->setScope($this->getScope());

        return $stream;
    }

    /**
     * Returns the first ResourcePull.
     *
     * @return ResourcePull
     */
    public function getPull1(): ResourcePull
    {
        return $this->pull1;
    }

    /**
     * Returns the second ResourcePull.
     *
     * @return ResourcePull
     */
    public function getPull2(): ResourcePull
    {
        return $this->pull2;
    }

    /**
     * Returns the current value from the active pull.
     *
     * @return mixed
     */
    public function current(): mixed
    {
        return $this->currentPull->current();
    }

    /**
     * Returns the current key from the active pull.
     *
     * @return mixed
     */
    public function key(): mixed
    {
        return $this->currentPull->key();
    }

    /**
     * Advances the active pull, switching to pull2 when pull1 is exhausted.
     *
     * @throws \OutOfBoundsException If both pulls are exhausted.
     */
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

    /**
     * Returns true if either pull still has data.
     *
     * @return bool
     */
    public function valid(): bool
    {
        return $this->hasNext();
    }

    /**
     * Consumes both pulls and returns all chunks as a single array.
     *
     * Rewinds before reading.
     *
     * @return array
     */
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
