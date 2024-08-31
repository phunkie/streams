<?php

namespace Phunkie\Streams\Infinite;

class Iterate implements Infinite
{
    private \Generator $values;

    public function __construct(readonly private \Closure|string $f, readonly private int $start)
    {
        $this->values = $this->generate();
    }

    public function getValues(): \Generator
    {
        return $this->values;
    }

    private function generate(): \Generator
    {
        for ($i = $this->start; true; $i = ($this->f)($i)) {
            yield $i;
        }
    }

    public function reset(): void
    {
        $this->values = $this->generate();
    }
}