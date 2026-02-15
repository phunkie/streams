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
 * Show operations for ResourcePullConcat. Displays concatenated pull representations.
 *
 * @method Pull getPull1()
 * @method Pull getPull2()
 */
trait ShowOps
{
    /**
     * Return the type representation for concatenated resource pulls.
     *
     * @return string
     */
    public function showType(): string
    {
        return 'Byte';
    }

    /**
     * Return a string showing both pulls joined by "++".
     *
     * @return string
     */
    public function toString(): string
    {
        return $this->getPull1()->toString() . " ++ " . $this->getPull2()->toString();
    }
}
