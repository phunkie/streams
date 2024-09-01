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
        $this->getScope()->addCallable('evalMap', $f);

        return $this;
    }
}
