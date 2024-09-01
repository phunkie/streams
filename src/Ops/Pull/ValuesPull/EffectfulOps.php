<?php

/**
 * This file is part of Phunkie Streams,a PHP functional library
 * to work with Streams.
 *
 * Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams\Ops\Pull\ValuesPull;

use Phunkie\Streams\IO\IO;
use function Phunkie\Streams\Functions\pipeline\evalFilter;
use function Phunkie\Streams\Functions\pipeline\evalMap;

/**
 * This trait allows you to add operations with side effects to the scope of the stream.
 *
 * @method getScope()
 */
trait EffectfulOps
{
    /**
     * This method allows you to apply map with an effectful function to the scope of the stream.
     *
     * @param callable $f A => IO<B>
     * @return $this
     */
    public function evalMap($f)
    {
        $this->addPipeline(evalMap($f)[IO::class]);

        return $this;
    }

    public function evalFilter($f)
    {
        $this->addPipeline(evalFilter($f)[IO::class]);

        return $this;
    }
}
