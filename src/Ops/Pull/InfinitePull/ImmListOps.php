<?php

namespace Phunkie\Streams\Ops\Pull\InfinitePull;

use Phunkie\Streams\Pull\ValuesPull;

trait ImmListOps
{

    public function take(int $n): ValuesPull
    {
        $values = [];

        for ($i = 0; $i < $n; $i++) {
            if ($this->valid()) {
                $values[] = $this->pull();
            }
        }
        $valuesPull = new ValuesPull(
            ...$values
        );
        $valuesPull->setScope($this->getScope());
        return $valuesPull;
    }
}