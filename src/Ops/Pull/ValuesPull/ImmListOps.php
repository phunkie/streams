<?php

namespace Phunkie\Streams\Ops\Pull\ValuesPull;

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
        $this->getScope()->addCallable('filter', $f);

        return $this;
    }

    public function interleave(... $other): ValuesPull
    {
        $this->getScope()->addCallable('interleave', function() use ($other) {
            $interleaved = array();

            $pulls = [];
            $pulls[] = $this->getValues();
            $pulls = array_merge($pulls, array_map(fn($x) => $x->getValues(), $other));
            for ($pulls = $pulls; count($pulls); $pulls = array_filter($pulls)) {
                foreach ($pulls as &$pull) {
                    $interleaved[] = array_shift($pull);
                }
            }
            return $interleaved;
        });


        return $this;
    }
}
