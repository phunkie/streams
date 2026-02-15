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

namespace Phunkie\Streams\Ops\Pull;

use Phunkie\Effect\IO\IO;

use function Phunkie\Streams\Functions\transformation\evalFilter;
use function Phunkie\Streams\Functions\transformation\evalMap;
use function Phunkie\Streams\Functions\transformation\evalTap;

use Phunkie\Streams\Type\Scope;

/**
 * This trait allows you to add operations with side effects to the scope of the stream.
 *
 * @method Scope getScope()
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
        $this->appendTransformation(evalMap($f)[IO::class]);

        return $this;
    }

    /**
     * Apply an effectful function for side effects, keeping the original element.
     *
     * @param callable $f A => IO<void>
     * @return $this
     */
    public function evalTap($f)
    {
        $this->appendTransformation(evalTap($f)[IO::class]);

        return $this;
    }

    /**
     * Filter elements using an effectful predicate that returns IO<bool>.
     *
     * @param callable $f A => IO<bool>
     * @return $this
     */
    public function evalFilter($f)
    {
        $this->appendTransformation(evalFilter($f)[IO::class]);

        return $this;
    }

    /**
     * FlatMap with an effectful function. Delegates to evalFilter transformation.
     *
     * @param callable $f A => IO<iterable<B>>
     * @return $this
     */
    public function evalFlatMap($f)
    {
        $this->appendTransformation(evalFilter($f)[IO::class]);

        return $this;
    }
}
