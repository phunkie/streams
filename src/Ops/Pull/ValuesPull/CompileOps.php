<?php

namespace Phunkie\Streams\Ops\Pull\ValuesPull;

use Phunkie\Streams\IO\IO;
use Phunkie\Streams\Type\Pull;
use Phunkie\Streams\Type\Scope;
use Phunkie\Types\ImmList;
use function Phunkie\Functions\function1\identity;
use const Phunkie\Functions\function1\identity;

/**
 * @method array getValues()
 * @method Scope getScope()
 * @method Pull pull()
 */
trait CompileOps
{
    public function toList(): ImmList | IO
    {
        $list = $this->runPipeline($this->getValues());

        return $list instanceof IO ? $list : new ImmList(...$list);
    }

    public function toArray(): array
    {
        return $this->runPipeline($this->getValues());
    }

    public function runLog($bytes): array
    {
        return $this->toArray();
    }

    public function drain(): IO
    {
        return new IO(function () {
            while ($this->valid()) {
                $this->runPipeline([$this->current()]);
                $this->next();
            }

            return Unit();
        });
    }
}
