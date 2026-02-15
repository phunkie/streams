<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams;

use Phunkie\Effect\IO\IO;
use Phunkie\Types\ImmList;

/**
 * Contract for compiling a stream into a materialised collection.
 */
interface Compilable
{
    /**
     * Compile the stream into an ImmList, or an IO action for effectful streams.
     *
     * @return ImmList|IO
     */
    public function toList(): ImmList | IO;

    /** Compile the stream into a plain PHP array. */
    public function toArray(): array;
}
