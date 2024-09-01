<?php

namespace Phunkie\Streams\Infinite;

final class Range implements Infinite
{
    private \Generator $values;

    public function __construct(private int $start, private int $end, private int $step = 1)
    {
        $this->values = $this->generate();
    }

    private function generate(): \Generator
    {
        for ($i = $this->start; $i < $this->end; $i += $this->step) {
            yield $i;
        }
    }

    public function getValues(): \Generator
    {
        return $this->values;
    }

    public function reset(): void
    {
        $this->values = $this->generate();
    }
}
