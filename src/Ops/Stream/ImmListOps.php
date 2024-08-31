<?php

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

    public function interleave(... $streams): Stream
    {
        $pulls = array_map(fn($x) => $x->getPull(), $streams);
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
}
