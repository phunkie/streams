<?php

namespace Phunkie\Streams\Stream;

use Phunkie\Streams\Ops\Stream\CompileOps;
use Phunkie\Streams\Type\Pull;

class Compiler
{
    use CompileOps;

    public function __construct(private Pull $pull, private int $bytes)
    {
    }

    public function getPull(): Pull
    {
        return $this->pull;
    }

    public function getBytes(): int
    {
        return $this->bytes;
    }
}