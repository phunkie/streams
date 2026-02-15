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
use Phunkie\Streams\IO\Resource;
use Phunkie\Types\ImmList;

/**
 * Compilation operations for ResourcePull. Materialises resource-backed data into collections.
 *
 * @method array getValues()
 * @method \Phunkie\Streams\Type\Scope getScope()
 * @method mixed pull()
 */
trait CompileOps
{
    /**
     * Compile into an ImmList, applying any scope-registered maps.
     *
     * @return ImmList
     */
    public function toList(): ImmList
    {
        $list = ImmList(...$this->getValues());

        foreach ($this->getScope()->getMaps() as $f) {
            $list = $list->map($f);
        }

        return $list;
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
     * Run the resource pull and log output as IO, reading up to 10 chunks.
     *
     * @param mixed $bytes Byte size hint for reading
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
