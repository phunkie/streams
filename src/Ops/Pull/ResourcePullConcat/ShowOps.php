<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams\Ops\Pull\ResourcePullConcat;

use Phunkie\Streams\Type\Pull;

/**
 * @method Pull getPull1()
 * @method Pull getPull2()
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
