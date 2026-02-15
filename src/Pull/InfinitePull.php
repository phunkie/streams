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

use const Phunkie\Functions\function1\identity;

use Phunkie\Streams\Infinite\Infinite;
use Phunkie\Streams\Ops\Pull\EffectfulOps;
use Phunkie\Streams\Ops\Pull\InfinitePull\CompileOps;
use Phunkie\Streams\Ops\Pull\InfinitePull\FunctorOps;
use Phunkie\Streams\Ops\Pull\InfinitePull\ImmListOps;
use Phunkie\Streams\Ops\Pull\InfinitePull\IteratorOps;
use Phunkie\Streams\Ops\Pull\InfinitePull\ShowOps;
use Phunkie\Streams\Ops\Pull\TransformationOps;
use Phunkie\Streams\Type\Pull;
use Phunkie\Streams\Type\Scope;
use Phunkie\Streams\Type\Stream;

/**
 * Pull backed by an Infinite generator source.
 *
 * Produces values lazily from a generator, suitable for unbounded or
 * computationally-defined streams (e.g. repeat, iterate, unfold).
 */
class InfinitePull implements Pull
{
    use CompileOps;
    use FunctorOps;
    use ShowOps;
    use IteratorOps;
    use ImmListOps;
    use TransformationOps;
    use EffectfulOps;

    private Infinite $infinite;
    private int $bytes;
    private Scope $scope;

    /**
     * @param Infinite $infinite The generator-based infinite source.
     * @param int      $bytes    Chunk size hint for downstream consumers.
     */
    public function __construct(Infinite $infinite, int $bytes = 256)
    {
        $this->infinite = $infinite;
        $this->bytes = $bytes;
        $this->scope = new Scope(identity);
    }

    /**
     * Returns the current value and advances to the next element.
     *
     * Unlike ValuesPull::pull(), this consumes the element so that successive
     * calls yield successive generator values.
     *
     * @return mixed The value before advancing.
     */
    public function pull()
    {
        $current = $this->current();

        $this->next();

        return $current;
    }

    /** Returns the underlying generator of values. */
    public function getValues(): \Generator
    {
        return $this->infinite->getValues();
    }

    /** Converts this pull into an infinite Stream, preserving the current scope. */
    public function toStream(): Stream
    {
        $stream = Stream::fromInfinite($this->infinite, $this->bytes);
        $stream->setScope($this->getScope());

        return $stream;
    }

    /** Returns the wrapped Infinite source. */
    public function getInfinite(): Infinite
    {
        return $this->infinite;
    }

    /** Returns the current scope. */
    public function getScope(): Scope
    {
        return $this->scope;
    }

    /** Replace the current scope. */
    public function setScope(Scope $scope): static
    {
        $this->scope = $scope;

        return $this;
    }
}
