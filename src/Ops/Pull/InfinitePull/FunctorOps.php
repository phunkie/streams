<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams\Ops\Pull\InfinitePull;

use function Phunkie\Streams\Functions\transformation\map;

/**
 * @method getScope
 */
trait FunctorOps
{
    public function mapOutput($f): static
    {
        return $this->map($f);
    }

    public function map($f): static
    {
        $this->appendTransformation(map($f));

        return $this;
    }
}
