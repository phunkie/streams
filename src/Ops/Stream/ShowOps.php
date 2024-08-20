<?php

namespace Phunkie\Streams\Ops\Stream;

use Phunkie\Streams\Pull\ResourcePull;

/**
 * @method getPull(): Phunkie\Streams\Pull
 */
trait ShowOps
{
    public function showType(): string
    {
        return sprintf("Stream<%s, %s>", $this->getTypeVariables()[0], $this->getTypeVariables()[1]);
    }

    public function getTypeVariables(): array
    {
        return [$this->getPull() instanceof ResourcePull ? IO : Pure, $this->getPull()->showType()];
    }

    public function toString(): string
    {
        return 'Stream(' . $this->getPull()->toString() . ')';
    }
}