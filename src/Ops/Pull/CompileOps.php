<?php

namespace Phunkie\Streams\Ops\Pull;

use Phunkie\Streams\IO\IO;
use Phunkie\Streams\IO\Resource;
use Phunkie\Types\ImmList;
use function Phunkie\Functions\io\io;

trait CompileOps
{
    public function toList(): ImmList
    {
        $list = ImmList(...$this->getUnderlying());

        foreach ($this->getScope()->getMaps() as $f) {
            $list = $list->map($f);
        }

        return $list;
    }

    public function toArray(): array
    {
        return $this->getUnderlying();
    }

    public function runLog($bytes)
    {
        return new IO(function() use ($bytes) {
            $log = [];
            $count = 0;

            do {
                $bit = $this->getUnderlying()->pull($bytes);

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