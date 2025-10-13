<?php

namespace Phunkie\Streams\Ops\Pull\ValuesPull;

use function Phunkie\Streams\Functions\transformation\chunk;
use function Phunkie\Streams\Functions\transformation\dropWhile;
use function Phunkie\Streams\Functions\transformation\filter;
use function Phunkie\Streams\Functions\transformation\interleave;
use function Phunkie\Streams\Functions\transformation\takeWhile;

use Phunkie\Streams\Pull\ValuesPull;

trait ImmListOps
{
    public function take(int $n): ValuesPull
    {
        $valuesPull = new ValuesPull(
            ...array_slice($this->getValues(), 0, $n)
        );
        $valuesPull->setScope($this->getScope());

        return $valuesPull;
    }

    public function filter(callable $f): ValuesPull
    {
        $this->appendTransformation(filter($f));

        return $this;
    }

    public function interleave(...$other): ValuesPull
    {
        $this->appendTransformation(interleave(...$other));

        return $this;
    }

    public function takeWhile(callable $predicate): ValuesPull
    {
        $this->appendTransformation(takeWhile($predicate));

        return $this;
    }

    public function dropWhile(callable $predicate): ValuesPull
    {
        $this->appendTransformation(dropWhile($predicate));

        return $this;
    }

    public function chunk(int $size): ValuesPull
    {
        $this->appendTransformation(chunk($size));

        return $this;
    }
}
