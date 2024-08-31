<?php

namespace Phunkie\Streams\Infinite;

class Constant implements Infinite
{
    public function __construct(private mixed $pattern)
    {
    }

    public function getValues(): \Generator
    {
        while (true) {
            yield $this->pattern;
        }
    }

    public function reset(): void
    {
        // do nothing
    }
}
