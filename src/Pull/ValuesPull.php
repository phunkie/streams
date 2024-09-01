<?php

namespace Phunkie\Streams\Pull;

use Phunkie\Streams\IO\IO;
use Phunkie\Streams\Ops\Pull\ValuesPull\CompileOps;
use Phunkie\Streams\Ops\Pull\ValuesPull\EffectfulOps;
use Phunkie\Streams\Ops\Pull\ValuesPull\FunctorOps;
use Phunkie\Streams\Ops\Pull\ValuesPull\ImmListOps;
use Phunkie\Streams\Ops\Pull\ValuesPull\InteratorOps;
use Phunkie\Streams\Ops\Pull\ValuesPull\PipelineOps;
use Phunkie\Streams\Ops\Pull\ValuesPull\ShowOps;
use Phunkie\Streams\Type\Pull;
use Phunkie\Streams\Type\Scope;
use Phunkie\Streams\Type\Stream;

class ValuesPull implements Pull
{
    use ShowOps;
    use CompileOps;
    use FunctorOps;
    use InteratorOps;
    use ImmListOps;
    use EffectfulOps;
    use PipelineOps;

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

    public function getScope(): Scope
    {
        return $this->scope;
    }

    public function applyScope(array $chunk): array | IO
    {
        return $this->getScope()->apply($chunk);
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

    public function getIndex(): int
    {
        return $this->index;
    }

    public function setIndex(int $index): static
    {
        $this->index = $index;

        return $this;
    }
}
