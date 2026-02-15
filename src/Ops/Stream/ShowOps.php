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
 * Show operations for Stream. Provides type and string representations.
 *
 * @method Pull getPull()
 * @method string getEffect()
 */
trait ShowOps
{
    /**
     * Return the type representation, e.g. "Stream<IO, Int>".
     *
     * @return string
     */
    public function showType(): string
    {
        return sprintf("Stream<%s, %s>", $this->getTypeVariables()[0], $this->getTypeVariables()[1]);
    }

    /**
     * Return the type variables [effect, pullType] for this stream.
     *
     * @return array{0: string, 1: string}
     */
    public function getTypeVariables(): array
    {
        return [$this->getEffect(), $this->getPull()->showType()];
    }

    /**
     * Return a short string representation of the stream.
     *
     * @return string
     */
    public function toString(): string
    {
        return 'Stream(..)';
    }
}
