<?php

namespace Phunkie\Streams\Infinite;

class Repeat implements Infinite
{
    private array $pattern;

    public function __construct(...$pattern)
    {
        $this->pattern = $pattern;
    }

    public function getValues(): \Generator
    {
        while (true) {
            if (key($this->pattern) === null) {
                reset($this->pattern);
            }
            yield current($this->pattern);
            next($this->pattern);
        }
    }

    public function reset(): void
    {
        reset($this->pattern);
    }
}