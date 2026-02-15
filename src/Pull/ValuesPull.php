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

use Phunkie\Streams\Ops\Pull\EffectfulOps;
use Phunkie\Streams\Ops\Pull\TransformationOps;
use Phunkie\Streams\Ops\Pull\ValuesPull\CompileOps;
use Phunkie\Streams\Ops\Pull\ValuesPull\FunctorOps;
use Phunkie\Streams\Ops\Pull\ValuesPull\ImmListOps;
use Phunkie\Streams\Ops\Pull\ValuesPull\IteratorOps;
use Phunkie\Streams\Ops\Pull\ValuesPull\MonadOps;
use Phunkie\Streams\Ops\Pull\ValuesPull\ShowOps;
use Phunkie\Streams\Type\Pull;
use Phunkie\Streams\Type\Scope;
use Phunkie\Streams\Type\Stream;

/**
 * Pull backed by an in-memory array of values.
 *
 * Wraps a variadic list of values and exposes them through the Pull/Iterator interface.
 * Supports functor, monad, and compilation operations via traits.
 */
class ValuesPull implements Pull
{
    use ShowOps;
    use CompileOps;
    use FunctorOps;
    use MonadOps;
    use IteratorOps;
    use ImmListOps;
    use EffectfulOps;
    use TransformationOps;

    private $values;
    private $index;
    private Scope $scope;

    /**
     * @param mixed ...$values Values to expose through this pull.
     */
    public function __construct(...$values)
    {
        $this->values = $values;
        $this->index = 0;
        $this->scope = new Scope();
    }

    /**
     * Returns the current value without advancing the iterator.
     *
     * @return mixed The value at the current index.
     */
    public function pull(): mixed
    {
        return $this->current();
    }

    /**
     * Returns the underlying values array.
     *
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /** Returns the current scope. */
    public function getScope(): Scope
    {
        return $this->scope;
    }

    /** Converts this pull into a Stream, preserving the current scope. */
    public function toStream(): Stream
    {
        $stream = Stream(...$this->values);
        $stream->setScope($this->getScope());

        return $stream;
    }

    /** Replace the current scope. */
    public function setScope(Scope $scope): static
    {
        $this->scope = $scope;

        return $this;
    }

    /** Returns the current iterator index. */
    public function getIndex(): int
    {
        return $this->index;
    }

    /** Set the iterator index to a specific position. */
    public function setIndex(int $index): static
    {
        $this->index = $index;

        return $this;
    }
}
