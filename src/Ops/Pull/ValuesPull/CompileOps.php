<?php

namespace Phunkie\Streams\Ops\Pull\ValuesPull;

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
    public function toList(): ImmList
    {

        $list = ImmList(...$this->getValues());
        return $this->applyScope($list);
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