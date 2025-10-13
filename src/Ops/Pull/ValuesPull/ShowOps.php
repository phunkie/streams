<?php

namespace Phunkie\Streams\Ops\Pull\ValuesPull;

use function Phunkie\Functions\show\showArrayType;
use function Phunkie\Functions\show\showValue;

trait ShowOps
{
    public function showType(): string
    {
        return showArrayType($this->getValues());
    }

    public function toString(): string
    {
        return join(', ', array_map(fn ($x) => showValue($x), $this->getValues()));
    }
}
