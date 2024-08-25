<?php

namespace Phunkie\Streams\Ops\Pull\ResourcePullConcat;

use Phunkie\Streams\Pull\ResourcePull;
use Phunkie\Streams\Pull\ValuesPull;
use function Phunkie\Functions\show\showArrayType;
use function Phunkie\Functions\show\showValue;

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
