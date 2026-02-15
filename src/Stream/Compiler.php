<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams\Stream;

use Phunkie\Effect\IO\IO;
use Phunkie\Streams\Ops\Stream\CompileOps;
use Phunkie\Streams\Type\Pull;
use Phunkie\Types\ImmList;

/**
 * Compiles a stream's Pull into a materialised value (list, array, or execution log).
 *
 * Accessible as a property on Stream via $stream->compile, then chained
 * with ->toList, ->toArray, ->drain, or ->runLog (as properties or methods).
 *
 * @property ImmList|IO $toList  Compile the stream to an ImmList (or IO for effectful streams).
 * @property array      $toArray Compile the stream to a plain PHP array.
 * @property array      $runLog  Execute the stream and return the execution log.
 */
class Compiler
{
    use CompileOps;

    /**
     * @param Pull $pull  The pull source to compile.
     * @param int  $bytes Chunk size in bytes for resource-based pulls.
     */
    public function __construct(private Pull $pull, private int $bytes)
    {
    }

    /**
     * Magic property accessor for drain, toList, toArray, and runLog.
     *
     * @throws \Error If the property is not recognised.
     */
    public function __get($property)
    {
        return match($property) {
            'drain' => $this->drain(),
            'toList' => $this->toList(),
            'toArray' => $this->toArray(),
            'runLog' => $this->runLog(),
            default => throw new \Error("value $property is not a member of Compiler")
        };
    }

    /** Return the Pull being compiled. */
    public function getPull(): Pull
    {
        return $this->pull;
    }

    /** Return the configured chunk size in bytes. */
    public function getBytes(): int
    {
        return $this->bytes;
    }

    /** Execute the stream for its side effects, discarding the output. */
    private function drain()
    {
        return $this->getPull()->drain();
    }
}
