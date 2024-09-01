<?php

namespace Phunkie\Streams\Ops\Pull\InfinitePull;

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
        $values = [];
        for ($i = 0; $i < 10; $i++) {
            $values[] = $this->getInfinite()->getValues()->current();
            $this->getInfinite()->getValues()->next();
        }
        $this->getInfinite()->reset();

        return '[' . implode(', ', $values) . ', ...]';
    }
}
