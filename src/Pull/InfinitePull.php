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

    public function __construct(Infinite $infinite, int $bytes = 256)
    {
        $this->infinite = $infinite;
        $this->bytes = $bytes;
        $this->scope = new Scope(identity);
    }

    public function pull()
    {
        $current = $this->current();

        $this->next();

        return $current;
    }

    public function getValues(): \Generator
    {
        return $this->infinite->getValues();
    }

    public function toStream(): Stream
    {
        $stream = Stream::fromInfinite($this->infinite, $this->bytes);
        $stream->setScope($this->getScope());

        return $stream;
    }

    public function getInfinite(): Infinite
    {
        return $this->infinite;
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
}
