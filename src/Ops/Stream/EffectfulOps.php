<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams\Ops\Stream;

use Phunkie\Streams\Type\Stream;

trait EffectfulOps
{
    public function evalMap($f): Stream
    {
        $this->getPull()->evalMap($f);

        return $this;
    }

    public function evalTap($f): Stream
    {
        $this->getPull()->evalTap($f);

        return $this;
    }

    public function evalFilter($f): Stream
    {
        $this->getPull()->evalFilter($f);

        return $this;
    }

    public function evalFlatMap($f): Stream
    {
        $this->getPull()->evalFlatMap($f);

        return $this;
    }
}
