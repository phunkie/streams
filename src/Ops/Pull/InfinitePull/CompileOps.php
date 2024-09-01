<?php

namespace Phunkie\Streams\Ops\Pull\InfinitePull;

use Phunkie\Streams\IO\IO;
use Phunkie\Streams\IO\Resource;
use Phunkie\Types\ImmList;

trait CompileOps
{
    public function toList(): ImmList
    {
        $list = $this->runPipeline($this->getValues());

        return $list instanceof IO ? $list : new ImmList(...$list);
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
