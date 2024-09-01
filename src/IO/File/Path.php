<?php

namespace Phunkie\Streams\IO\File;

class Path
{
    public function __construct(readonly private string $pathname)
    {
    }

    public function toString(): string
    {
        return $this->pathname;
    }
}

