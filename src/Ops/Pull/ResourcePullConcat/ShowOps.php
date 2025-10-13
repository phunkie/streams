<?php

namespace Phunkie\Streams\Ops\Pull\ResourcePullConcat;

/**
 * @method getPull1
 * @method getPull2
 */
trait ShowOps
{
    public function showType(): string
    {
        return 'Byte';
    }

    public function toString(): string
    {
        return $this->getPull1()->toString() . " ++ " . $this->getPull2()->toString();
    }
}
