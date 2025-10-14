<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams\Ops\Pull\InfinitePull;

use function Phunkie\Effect\Functions\io\io;

use Phunkie\Effect\IO\IO;
use Phunkie\Streams\IO\Resource;
use Phunkie\Types\ImmList;

trait CompileOps
{
    public function toList(): ImmList | IO
    {
        $list = $this->runTransformations($this->getInfinite()->getValues());

        if ($list instanceof \Generator) {
            $chunk = [];
            for ($i = 0; $i < 10; $i++) {
                if (!$list->valid()) {
                    break;
                }
                $chunk[] = $list->current();
                $list->next();
            }

            if ($i === 9) {
                $chunk[] = '...';
            }

            return io(fn () => ImmList(...$chunk));
        }

        return $list instanceof IO ? $list : new ImmList(...$list);
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
