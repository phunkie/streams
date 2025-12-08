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

use Phunkie\Effect\IO\IO;
use Phunkie\Streams\Type\Pull;
use Phunkie\Types\ImmList;

/**
 * @method Pull getPull()
 */
trait CompileOps
{
    public function toList(): ImmList | IO
    {
        return $this->getPull()->toList();
    }

    public function toArray(): array
    {
        return $this->getPull()->toArray();
    }

    public function runLog()
    {
        return $this->getPull()->runLog($this->getBytes());
    }
}
