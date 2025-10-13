<?php

namespace Phunkie\Streams\Ops\Pull\ResourcePull;

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
