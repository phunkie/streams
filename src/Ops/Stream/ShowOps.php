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

use Phunkie\Streams\Type\Pull;

/**
 * @method Pull getPull()
 */
trait ShowOps
{
    public function showType(): string
    {
        return sprintf("Stream<%s, %s>", $this->getTypeVariables()[0], $this->getTypeVariables()[1]);
    }

    public function getTypeVariables(): array
    {
        return [$this->getEffect(), $this->getPull()->showType()];
    }

    public function toString(): string
    {
        return 'Stream(..)';
    }
}
