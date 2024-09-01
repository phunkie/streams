<?php

namespace Phunkie\Streams\Ops\Pull\ResourcePull;

use Phunkie\Streams\Pull\ResourcePull;
use Phunkie\Streams\Pull\ValuesPull;
use function Phunkie\Functions\show\showArrayType;
use function Phunkie\Functions\show\showValue;

trait ShowOps
{
    public function showType(): string
    {
        return 'Byte';
    }

    public function toString(): string
    {
        return '...';
    }
}
