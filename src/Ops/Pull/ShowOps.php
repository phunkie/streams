<?php

namespace Phunkie\Streams\Ops\Pull;

use Phunkie\Streams\Pull\ResourcePull;
use Phunkie\Streams\Pull\ValuesPull;
use function Phunkie\Functions\show\showArrayType;
use function Phunkie\Functions\show\showValue;

trait ShowOps
{
    public function showType(): string
    {
        return match(get_class($this)) {
            ValuesPull::class => showArrayType($this->getValues()),
            ResourcePull::class => 'Byte'
        };
    }

    public function toString(): string
    {
        return match(get_class($this)) {
            ValuesPull::class => join(', ', array_map(fn($x) => showValue($x), $this->getValues())),
            default => '...'
        };
    }
}