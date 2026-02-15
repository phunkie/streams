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
use Phunkie\Streams\Ops\Stream\EffectfulOps;
use Phunkie\Streams\Ops\Stream\FunctorOps;
use Phunkie\Streams\Ops\Stream\ImmListOps;
use Phunkie\Streams\Ops\Stream\MergeOps;
use Phunkie\Streams\Ops\Stream\MonadOps;
use Phunkie\Streams\Ops\Stream\ParallelOps;
use Phunkie\Streams\Ops\Stream\ShowOps;
use Phunkie\Streams\Pull\InfinitePull;
use Phunkie\Streams\Pull\ResourceObjectPull;
use Phunkie\Streams\Pull\ResourcePull;
use Phunkie\Streams\Pull\ValuesPull;
use Phunkie\Streams\Showable;
use Phunkie\Streams\Stream\Compiler;
use Phunkie\Types\ImmList;
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
    use MonadOps;
    use ImmListOps;
    use EffectfulOps;
    use ParallelOps;
    use MergeOps;

    /**
     * Constructor for the Stream class.
     *
     * @param Pull $pull The underlying pull mechanism that drives the Stream.
     * @param int $bytes The size in bytes for internal processing of the Stream.
     */
    private function __construct(private Pull $pull, private int $bytes)
    {
    }

    /** Create a pure stream from in-memory values. */
    public static function fromValues(...$pull): Stream
    {
        return new Stream(new ValuesPull(...$pull), 256);
    }

    /**
     * Create an effectful stream that reads from a file-system resource.
     *
     * @param Path $path  Path to the resource to read.
     * @param int  $bytes Chunk size in bytes for each pull.
     */
    public static function fromResource(Path $path, int $bytes = 256): Stream
    {
        $resourcePull = new ResourcePull($path, $bytes);

        return new Stream($resourcePull, $bytes);
    }

    /**
     * Create an effectful stream from an already-opened Resource object.
     *
     * @param \Phunkie\Streams\IO\Resource $resource An open resource handle.
     * @param int                          $bytes    Chunk size in bytes for each pull.
     */
    public static function fromResourceObject(\Phunkie\Streams\IO\Resource $resource, int $bytes = 4096): Stream
    {
        $resourceObjectPull = new ResourceObjectPull($resource, $bytes);

        return new Stream($resourceObjectPull, $bytes);
    }

    /** Create a stream from an arbitrary Pull implementation. */
    public static function fromPull(Pull $pull, int $bytes = 256): Stream
    {
        return new Stream($pull, $bytes);
    }

    /** Create a stream backed by an infinite generator. */
    public static function fromInfinite(Infinite $infinite, int $bytes = 256): Stream
    {
        return new Stream(new InfinitePull($infinite, $bytes), $bytes);
    }

    /** Return the underlying Pull that drives this stream. */
    protected function getPull(): Pull
    {
        return $this->pull;
    }

    /**
     * Magic property accessor for compile, repeat, runLog, toList, and toArray.
     *
     * @return mixed
     * @throws \Error If the property is not recognised.
     */
    public function __get($property): mixed
    {
        return match($property) {
            'compile' => $this->compile(),
            'repeat' => $this->repeat(),
            'runLog' => $this->runLog(),
            'toList' => $this->toList(),
            'toArray' => $this->toArray(),
            default => throw new \Error("value $property is not a member of Stream")
        };
    }

    /** Create a Compiler to materialise this stream's output. */
    public function compile(): Compiler
    {
        return new Compiler($this->getPull(), $this->getBytes());
    }

    /** Return a new stream that infinitely repeats this stream's values. */
    public function repeat(): Stream
    {
        return self::fromInfinite(repeat(...$this->getPull()->getValues()), $this->getBytes());
    }

    /**
     * Execute an effectful (resource-backed) stream and return its execution log.
     *
     * @throws \Error If called on a pure stream.
     */
    public function runLog(): array
    {
        return $this->getPull() instanceof ResourcePull ?
            (new Compiler($this->getPull(), $this->getBytes()))->runLog() :
            throw new \Error("Cannot call runlog on Pure Streams");
    }

    /** Assign a Scope (transformation pipeline) to this stream's pull.
     *
     * @return void
     */
    public function setScope(Scope $scope): void
    {
        $this->getPull()->setScope($scope);
    }

    /**
     * Compile a pure (values-backed) stream into an ImmList.
     *
     * @throws \Error If called on a resource-backed stream.
     */
    public function toList(): ImmList
    {
        if ($this->getPull() instanceof ValuesPull) {
            return $this->compile->toList();
        } else {
            throw new \Error("Can only call toList on Pure Streams");
        }
    }

    /** Return the Kind type arity (always 2: effect type + element type). */
    public function getTypeArity(): int
    {
        return 2;
    }

    /** Get the chunk size in bytes used for internal processing. */
    public function getBytes(): int
    {
        return $this->bytes;
    }

    /** Set the chunk size in bytes used for internal processing. */
    public function setBytes(int $bytes): static
    {
        $this->bytes = $bytes;

        return $this;
    }

    /** Return the underlying values from this stream's pull. */
    public function getValues(): array
    {
        return $this->pull->getValues();
    }

    /** Return the effect type identifier: "IO" for resource streams, "Pure" otherwise. */
    public function getEffect(): string
    {
        return ($this->getPull() instanceof ResourcePull || $this->getPull() instanceof ResourceObjectPull) ? IO : Pure;
    }

    /**
     * Compile a pure (values-backed) stream into a plain PHP array.
     *
     * @throws \Error If called on a resource-backed stream.
     */
    public function toArray(): array
    {
        if ($this->getPull() instanceof ValuesPull) {
            return $this->compile->toArray();
        } else {
            throw new \Error("Can only call toArray on Pure Streams");
        }
    }
}
