<?php

namespace Phunkie\Streams\Ops\Pull\ResourcePull;

use Phunkie\Streams\IO\IO;
use Phunkie\Streams\IO\Resource;
use Phunkie\Types\ImmList;

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
        return new IO(function() use ($bytes) {
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