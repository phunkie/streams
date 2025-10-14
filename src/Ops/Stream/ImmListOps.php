<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams\Ops\Stream;

use Phunkie\Streams\Pull\ResourcePull;
use Phunkie\Streams\Pull\ResourcePullConcat;
use Phunkie\Streams\Pull\ValuesPull;
use Phunkie\Streams\Type\Stream;

/**
 * @method getPull(): Pull
 */
trait ImmListOps
{
    public function concat(Stream $stream): Stream
    {
        return match(get_class($this->getPull())) {
            ValuesPull::class => match (get_class($stream->getPull())) {
                ValuesPull::class => Stream(...array_merge($this->getPull()->getValues(), $stream->getPull()->getValues())),
                default => throw new \Error('Cannot concatenate pure with non pure streams')
            },
            ResourcePull::class => match (get_class($stream->getPull())) {
                ResourcePull::class => Stream(new ResourcePullConcat($this->getPull(), $stream->getPull())),
                default => throw new \Error('Cannot concatenate pure with non pure streams')
            }
        };
    }

    public function interleave(...$streams): Stream
    {
        $pulls = array_map(fn ($x) => $x->getPull(), $streams);

        return new Stream($this->getPull()->interleave(...$pulls), $this->getBytes());
    }

    public function take(int $n): Stream
    {
        return new Stream($this->getPull()->take($n), $this->getBytes());
    }

    public function filter(callable $f): Stream
    {
        return new Stream($this->getPull()->filter($f), $this->getBytes());
    }

    public function through(callable $pipe): Stream
    {
        return $pipe($this);
    }

    public function takeWhile(callable $predicate): Stream
    {
        return new Stream($this->getPull()->takeWhile($predicate), $this->getBytes());
    }

    public function dropWhile(callable $predicate): Stream
    {
        return new Stream($this->getPull()->dropWhile($predicate), $this->getBytes());
    }

    public function chunk(int $size): Stream
    {
        return new Stream($this->getPull()->chunk($size), $this->getBytes());
    }

    public function merge(Stream ...$streams): Stream
    {
        // Merge is similar to concat but combines all streams
        $allValues = $this->getPull()->getValues();
        foreach ($streams as $stream) {
            $allValues = array_merge($allValues, $stream->getPull()->getValues());
        }

        return Stream(...$allValues);
    }

    public function zip(Stream $other): Stream
    {
        $thisValues = $this->getPull()->getValues();
        $otherValues = $other->getPull()->getValues();
        $zipped = [];
        $count = min(count($thisValues), count($otherValues));

        for ($i = 0; $i < $count; $i++) {
            $zipped[] = [$thisValues[$i], $otherValues[$i]];
        }

        return Stream(...$zipped);
    }
}
