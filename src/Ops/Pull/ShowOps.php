<?php

namespace Phunkie\Streams\Ops\Pull;

use function Phunkie\Functions\show\showArrayType;
use function Phunkie\Functions\show\showValue;

trait ShowOps
{
    public function showType(): string
    {
        return showArrayType($this->underlying);
    }

    public function toString(): string
    {
        return join(', ', array_map(fn($x) => showValue($x), $this->underlying));
    }
}