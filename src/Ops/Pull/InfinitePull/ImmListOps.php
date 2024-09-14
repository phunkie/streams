<?php

namespace Phunkie\Streams\Ops\Pull\InfinitePull;

use Phunkie\Streams\Infinite\Timer;
use Phunkie\Streams\Pull\InfinitePull;
use Phunkie\Streams\Pull\ValuesPull;

trait ImmListOps
{

    public function take(int $n): InfinitePull | ValuesPull
    {
        $infinite = $this->getInfinite();

        if ($infinite instanceof Timer) {
            $smallerInfinite = new InfinitePull(
                awakeEvery($infinite->getSeconds(), $n));
            $smallerInfinite->setScope($this->getScope());
            return $smallerInfinite;
        }

        $values = [];

        for ($i = 0; $i < $n; $i++) {
            if ($this->valid()) {
                $values[] = $this->pull();
            }
        }

        $infinite->reset();

        $valuesPull = new ValuesPull(
            ...$values
        );
        $valuesPull->setScope($this->getScope());
        return $valuesPull;
    }
}
