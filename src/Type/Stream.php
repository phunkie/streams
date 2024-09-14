<?php

/**
 * This file is part of Phunkie Streams,a PHP functional library
 * to work with Streams.
 *
 * Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams\Type;

use Phunkie\Cats\Show;
use Phunkie\Streams\Infinite\Infinite;
use Phunkie\Streams\IO\File\Path;
use Phunkie\Streams\Ops\Pull\PipelineOps;
use Phunkie\Streams\Ops\Stream\EffectfulOps;
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
 * @property Stream $repeat Provides access to a Stream instance that repeats this Stream.
 * @property array $runLog Provides access to the log of the execution of this Stream.
 */
class Stream implements Showable, Kind
{
    use Show, ShowOps {
        ShowOps::showType insteadof Show;
    }
    use FunctorOps;
    use ImmListOps;
    use EffectfulOps;

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

    public static function fromInfinite(Infinite $infinite, int $bytes = 256): Stream
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
            'compile' => $this->compile(),
            'repeat' => $this->repeat(),
            'runLog' => $this->runLog(),
            default => throw new \Error("value $property is not a member of Stream")
        };
    }

    public function compile(): Compiler
    {
        return new Compiler($this->getPull(), $this->getBytes());
    }

    public function repeat(): Stream
    {
        return self::fromInfinite(repeat(...$this->getPull()->getValues()), $this->getBytes());
    }

    public function runLog(): array
    {
        return $this->getPull() instanceof ResourcePull ?
            (new Compiler($this->getPull(), $this->getBytes()))->runLog() :
            throw new \Error("Cannot call runlog on Pure Streams");
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

    public function getEffect(): string
    {
        return $this->getPull() instanceof ResourcePull ? IO : Pure;
    }
}
