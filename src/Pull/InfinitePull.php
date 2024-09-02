<?php

namespace Phunkie\Streams\Pull;

use Phunkie\Streams\Infinite\Infinite;
use Phunkie\Streams\Ops\Pull\InfinitePull\CompileOps;
use Phunkie\Streams\Ops\Pull\InfinitePull\FunctorOps;
use Phunkie\Streams\Ops\Pull\InfinitePull\ImmListOps;
use Phunkie\Streams\Ops\Pull\InfinitePull\IteratorOps;
use Phunkie\Streams\Ops\Pull\InfinitePull\ShowOps;
use Phunkie\Streams\Ops\Pull\ValuesPull\PipelineOps;
use Phunkie\Streams\Type\Pull;
use Phunkie\Streams\Type\Scope;
use Phunkie\Streams\Type\Stream;
use const Phunkie\Functions\function1\identity;

class InfinitePull implements Pull
{
    use CompileOps;
    use FunctorOps;
    use ShowOps;
    use IteratorOps;
    use ImmListOps;
    use PipelineOps;

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

    public function toStream(): Stream
    {
        $stream = Stream::fromInfinite($this->infinite, $this->bytes);
        $stream->setScope($this->getScope());

        return $stream;
    }

    public function getInfinite()
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
