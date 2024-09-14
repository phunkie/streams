<?php

namespace Phunkie\Streams\Ops\Pull\InfinitePull;

use Phunkie\Streams\IO\IO;
use Phunkie\Streams\IO\Resource;
use Phunkie\Types\ImmList;
use function Phunkie\Functions\show\show;
use function Phunkie\Streams\Functions\io\io;

trait CompileOps
{
    public function toList(): ImmList | IO
    {
        $list = $this->runPipeline($this->getInfinite()->getValues());

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
