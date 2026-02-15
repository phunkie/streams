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
 * Compilation operations for ValuesPull. Materialises pull values into collections.
 *
 * @method array getValues()
 * @method Scope getScope()
 * @method Pull pull()
 */
trait CompileOps
{
    /**
     * Compile values into an ImmList, applying all pending transformations.
     * Returns IO if effectful transformations are present.
     *
     * @return ImmList|IO
     */
    public function toList(): ImmList | IO
    {
        $list = $this->runTransformations($this->getValues(), false);

        return $list instanceof IO ? $list : new ImmList(...$list);
    }

    /**
     * Compile values into a plain PHP array, applying all pending transformations.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->runTransformations($this->getValues());
    }

    /**
     * Run and log output. For ValuesPull, delegates to toArray.
     *
     * @param mixed $bytes Byte size (unused for value pulls)
     * @return array
     */
    public function runLog($bytes): array
    {
        return $this->toArray();
    }

    /**
     * Run all transformations for side effects, discarding output. Returns IO<Unit>.
     *
     * @return IO
     */
    public function drain(): IO
    {
        return new IO(function () {
            $this->runTransformations($this->getValues());

            return Unit();
        });
    }
}
