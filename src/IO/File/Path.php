<?php

namespace Phunkie\Streams\IO\File;

class Path
{
    public function __construct(private readonly string $pathname)
    {
    }

    public function toString(): string
    {
        return $this->pathname;
    }
}
