<?php

namespace Phunkie\Streams\Type;

use Phunkie\Streams\Compilable;
use Phunkie\Streams\Ops\Stream\CompileOps;
use Phunkie\Streams\Ops\Stream\FunctorOps;
use Phunkie\Streams\Ops\Stream\ShowOps;
use Phunkie\Streams\Showable;
use Phunkie\Streams\Stream\Compiler;
use Phunkie\Types\Kind;

/**
 * @property Compiler $compile
 */
class Stream implements Showable, Compilable, Kind
{
    use ShowOps;
    use CompileOps;
    use FunctorOps;

    private function __construct(private Pull $pull, private int $bytes)
    {
    }

    public static function instance(...$pull): Stream {
        return new Stream(new Pull(...$pull), 256);
    }

    protected function getPull(): Pull
    {
        return $this->pull;
    }

    public function __get($property)
    {
        return match($property) {
            'compile' => new Compiler($this->getPull(), $this->getBytes()),
            'runLog' => $this->getPull()->hasEffect() ?
                (new Compiler($this->getPull(), $this->getBytes()))->runLog() :
                throw new \Error("Cannot call runlog on Pure Streams"),
            default => throw new \Error("value $property is not a member of Stream")
        };
    }

    public function setScope(Scope $scope)
    {
        $this->getPull()->setScope($scope);
    }

    public function getTypeArity(): int
    {
        return 2;
    }

    public function getBytes(): int
    {
        return $this->bytes;
    }

    public function setBytes(int $bytes): static
    {
        $this->bytes = $bytes;

        return $this;
    }
}
