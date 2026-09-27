<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams\Ops\Pull\ValuesPull;

/**
 * Run log for ValuesPull: the whole output, since it is finite and in memory already.
 *
 * @method array toArray()
 */
trait LogOps
{
    /**
     * Run and log output. For ValuesPull, delegates to toArray.
     *
     * @param mixed $bytes Byte size (unused for value pulls)
     * @return array
     */
    public function runLog($bytes): array
    {
        return $this->toArray();
    }
}
