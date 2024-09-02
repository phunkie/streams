<?php

namespace Phunkie\Streams\Stream;

use Phunkie\Streams\IO\IO;
use Phunkie\Streams\Ops\Stream\CompileOps;
use Phunkie\Streams\Type\Pull;
use Phunkie\Types\ImmList;

/**
 * @property ImmList|IO $toList
 * @property array $toArray
 * @property array $runLog
 *
 */
class Compiler
{
    use CompileOps;

    public function __construct(private Pull $pull, private int $bytes)
    {
    }

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

    public function getPull(): Pull
    {
        return $this->pull;
    }

    public function getBytes(): int
    {
        return $this->bytes;
    }

    private function drain()
    {
        return $this->getPull()->drain();
    }
}
