<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams\Ops\Pull\ResourcePullConcat;

use Phunkie\Effect\IO\IO;
use Phunkie\Streams\IO\Resource;
use Phunkie\Streams\Type\Pull;
use Phunkie\Types\ImmList;

/**
 * @method Pull pull()
 */
trait CompileOps
{
    public function toList(): ImmList
    {
        $list = ImmList(...$this->getValues());

        foreach ($this->getScope()->getMaps() as $f) {
            $list = $list->map($f);
        }

        return $list;
    }

    public function toArray(): array
    {
        return $this->getValues();
    }

    public function runLog($bytes)
    {
        return new IO(function () use ($bytes) {
            $log = [];
            $count = 0;

            do {
                $bit = $this->pull();

                if ($bit !== Resource::EOF) {
                    $log[] = $bit;
                    $count++;
                }
            } while ($count < 10 && $bit !== Resource::EOF);

            if (count($log) > 0) {
                $log[] = '...';
            }

            return $log;
        });
    }
}
