<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams\Ops\Pull\ValuesPull;

use Phunkie\Effect\IO\IO;
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
        $list = $this->runTransformations($this->getValues(), false);

        return $list instanceof IO ? $list : new ImmList(...$list);
    }

    public function toArray(): array
    {
        return $this->runTransformations($this->getValues());
    }

    public function runLog($bytes): array
    {
        return $this->toArray();
    }

    public function drain(): IO
    {
        return new IO(function () {
            $this->runTransformations($this->getValues());

            return Unit();
        });
    }
}
