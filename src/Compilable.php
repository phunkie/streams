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

interface Compilable
{
    public function toList(): ImmList | IO;

    public function toArray(): array;
}
