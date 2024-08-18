<?php

namespace Phunkie\Streams\Type;

use Phunkie\Streams\Compilable;
use Phunkie\Streams\IO\Resource;
use Phunkie\Streams\Ops\Pull\CompileOps;
use Phunkie\Streams\Ops\Pull\FunctorOps;
use Phunkie\Streams\Ops\Pull\ShowOps;
use Phunkie\Streams\Showable;

class Pull implements Showable, Compilable
{
    use ShowOps;
    use CompileOps;
    use FunctorOps;

    private array|Resource $underlying;
    private Scope $scope;
    private $effect = Pure;


    public function __construct(...$underlying)
    {
        if ($this->isResource($underlying)) {
            $this->setEffect(IO);
            $this->underlying = $underlying[0];
        } else {
            $this->underlying = $underlying;
        }

        $this->scope = new Scope();
    }

    public function hasEffect(): bool
    {
        return $this->effect !== Pure;
    }

    public function getUnderlying(): array|Resource
    {
        return $this->underlying;
    }

    public function getScope(): Scope
    {
        return $this->scope;
    }

    public function setScope(Scope $scope)
    {
        $this->scope = $scope;
    }

    public function toStream(): Stream
    {
        $stream = Stream(...$this->getUnderlying());
        $stream->setScope($this->getScope());

        return $stream;
    }

    private function isResource($underlying): bool
    {
        return count($underlying) === 1 &&
            $underlying[0] instanceof Resource;
    }

    private function setEffect(string $effect)
    {
        $this->effect = $effect;

        return $this;
    }
}