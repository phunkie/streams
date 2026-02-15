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

/**
 * Compilation operations for InfinitePull. Materialises infinite sequences
 * with truncation (first 10 elements + "...") to prevent unbounded evaluation.
 *
 * @method \Phunkie\Streams\Infinite\Infinite getInfinite()
 * @method array getValues()
 * @method mixed pull()
 */
trait CompileOps
{
    /**
     * Compile into an ImmList, truncating to 10 elements for infinite sources.
     * Returns IO wrapping the list for infinite generators.
     *
     * @return ImmList|IO
     */
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

    /**
     * Compile into a plain PHP array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->getValues();
    }

    /**
     * Run the pull and log output as IO, capped at 10 elements for safety.
     *
     * @param mixed $bytes Byte size hint
     * @return IO
     */
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
