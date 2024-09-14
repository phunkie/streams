<?php

namespace Phunkie\Streams\Infinite;

class Timer implements Infinite
{
    private ?float $stopAt;
    private float $start;
    private float $seconds;
    private float $absoluteStart;
    private const NANOS_PER_SECOND = 1000000000;
    private int $count = 0;
    private ?int $limit;
    private bool $isFraction;

    public function __construct(float $seconds, ?float $stopAt)
    {
        $this->start = microtime(true);
        $this->absoluteStart = $this->start;
        $this->seconds = $seconds;
        $this->isFraction = $seconds - floor($seconds) !== 0.0;
        $this->count = 0;
        if ($this->isFraction) {
            $this->stopAt = $stopAt;
        } else {
            $this->stopAt = $stopAt === null ? null : $this->start + $stopAt;
        }

        $this->limit = $this->isFraction ? $this->stopAt : $stopAt;
    }

    public function getValues(): \Generator
    {
        while ($this->stopAt === null || $this->end()) {
            if (microtime(true) - $this->start >= $this->seconds) {
                $this->start = microtime(true);
                $this->count++;
                yield ($this->start * self::NANOS_PER_SECOND) -
                    ($this->absoluteStart * self::NANOS_PER_SECOND) .
                    " nanoseconds";
            }
        }
    }

    public function reset(): void
    {
        $this->start = microtime(true);
    }

    public function getSeconds(): float
    {
        return $this->seconds;
    }

    /**
     * @return bool
     */
    public function end(): bool
    {
        if ($this->isFraction) {
            return $this->count < $this->limit;
        }
        return $this->stopAt === null || $this->stopAt > $this->start;
    }
}
