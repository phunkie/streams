<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams\Infinite;

/**
 * Timer-based infinite stream that yields elapsed nanoseconds at regular intervals.
 *
 * When the interval is a fractional second, stopAt is treated as an emission count.
 * When the interval is a whole second, stopAt is treated as a duration in seconds.
 */
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

    /**
     * @param float      $seconds Interval between emissions in seconds
     * @param float|null $stopAt  Stop condition: emission count (fractional interval) or duration in seconds (whole interval), null for infinite
     */
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

    /**
     * Yield elapsed nanoseconds (as a string) at each interval tick.
     *
     * {@inheritdoc}
     */
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

    /** {@inheritdoc} */
    public function reset(): void
    {
        $this->start = microtime(true);
    }

    /**
     * @return float The interval in seconds between emissions
     */
    public function getSeconds(): float
    {
        return $this->seconds;
    }

    /**
     * Check whether the timer should continue emitting.
     *
     * @return bool True if the timer has not yet reached its stop condition
     */
    public function end(): bool
    {
        if ($this->isFraction) {
            return $this->count < $this->limit;
        }

        return $this->stopAt === null || $this->stopAt > $this->start;
    }
}
