<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams\Ops\Pull\ResourcePull;

use Phunkie\Effect\IO\IO;

/**
 * Run log for resource-backed pulls: a peek at the first chunks, since the source may be unbounded.
 *
 * @method \Generator elements()
 */
trait LogOps
{
    /**
     * Run the pull and log output as IO, reading up to 10 chunks.
     *
     * @param mixed $bytes Byte size hint for reading
     * @return IO
     */
    public function runLog($bytes): IO
    {
        return new IO(function () {
            $log = [];
            foreach ($this->elements() as $chunk) {
                $log[] = $chunk;
                if (10 === count($log)) {
                    break;
                }
            }

            return [] === $log ? [] : [...$log, '...'];
        });
    }
}
