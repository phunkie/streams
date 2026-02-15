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
 * Compilation operations for Stream. Materialises the stream into concrete values.
 *
 * @method Pull getPull()
 * @method int getBytes()
 */
trait CompileOps
{
    /**
     * Compile the stream into an ImmList, or an IO if effectful transformations are present.
     *
     * @return ImmList|IO
     */
    public function toList(): ImmList | IO
    {
        return $this->getPull()->toList();
    }

    /**
     * Compile the stream into a plain PHP array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->getPull()->toArray();
    }

    /**
     * Run the stream and collect output as a log. Used for resource-based streams.
     *
     * @return array|IO
     */
    public function runLog()
    {
        return $this->getPull()->runLog($this->getBytes());
    }
}
