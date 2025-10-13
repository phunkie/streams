<?php

namespace Phunkie\Streams\Ops\Pull\InfinitePull;

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
