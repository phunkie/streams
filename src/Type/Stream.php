<?php

namespace Phunkie\Streams\Type;

use Phunkie\Cats\Show;
use Phunkie\Streams\Infinite\Constructor;
use Phunkie\Streams\IO\File\Path;
use Phunkie\Streams\Ops\Stream\FunctorOps;
use Phunkie\Streams\Ops\Stream\ImmListOps;
use Phunkie\Streams\Ops\Stream\ShowOps;
use Phunkie\Streams\Pull\InfinitePull;
use Phunkie\Streams\Pull\ResourcePull;
use Phunkie\Streams\Pull\ValuesPull;
use Phunkie\Streams\Showable;
use Phunkie\Streams\Stream\Compiler;
use Phunkie\Types\Kind;

/**
 * The Stream class represents a lazy, functional stream of data in Phunkie.
 *
 * @property Compiler $compile Provides access to a Compiler instance for this Stream.
 */
class Stream implements Showable, Kind
{
    use Show, ShowOps {
        ShowOps::showType insteadof Show;
    }
    use FunctorOps;
    use ImmListOps;

    /**
     * Constructor for the Stream class.
     *
     * @param Pull $pull The underlying pull mechanism that drives the Stream.
     * @param int $bytes The size in bytes for internal processing of the Stream.
     */private function __construct(private Pull $pull, private int $bytes)
    {
    }

    public static function fromValues(...$pull): Stream {
        return new Stream(new ValuesPull(...$pull), 256);
    }

    public static function fromResource(Path $path, int $bytes = 256)
    {
        $resourcePull = new ResourcePull($path, $bytes);
        return new Stream($resourcePull, $bytes);
    }

    public static function fromInfinite(Constructor $infinite, int $bytes = 256): Stream
    {
        return new Stream(new InfinitePull($infinite, $bytes), $bytes);
    }

    protected function getPull(): Pull
    {
        return $this->pull;
    }

    public function __get($property)
    {
        return match($property) {
            'compile' => new Compiler($this->getPull(), $this->getBytes()),
            'runLog' => $this->getPull() instanceof ResourcePull ?
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
