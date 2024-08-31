<?php

namespace Phunkie\Streams\Ops\Pull\ValuesPull;

use Phunkie\Streams\IO\IO;
use Phunkie\Streams\Type\Pull;
use Phunkie\Streams\Type\Scope;
use Phunkie\Types\ImmList;

/**
 * @method array getValues()
 * @method Scope getScope()
 * @method Pull pull()
 */
trait CompileOps
{
    public function toList(): ImmList | IO
    {

        $list = $this->applyScope($this->getValues());

        return $list instanceof IO ? $list : new ImmList(...$list);
    }

    public function toArray(): array
    {
        return $this->getValues();
    }

    public function runLog($bytes): array
    {
        return $this->toArray();
    }
}