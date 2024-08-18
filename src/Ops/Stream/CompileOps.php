<?php

namespace Phunkie\Streams\Ops\Stream;

use Phunkie\Cats\IO;
use Phunkie\Types\ImmList;

/**
 * @method getPull(): Phunkie\Streams\Type\Pull
 */
trait CompileOps
{
    public function toList(): ImmList
    {
        return $this->getPull()->toList();
    }

    public function toArray(): array
    {
        return $this->getPull()->toArray();
    }

    public function runLog()
    {
        return $this->getPull()->runLog($this->getBytes());
    }
}