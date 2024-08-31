<?php

namespace Phunkie\Streams\Ops\Pull\ValuesPull;

use Phunkie\Cats\IO;

/**
 * @method getScope()
 */
trait EffectfulOps
{
    /**
     * This method allows you to apply map with an effectful function to the scope of the stream.
     *
     * @param IO<mixed> $f
     * @return mixed
     */
    public function evalMap(IO $f)
    {
        $this->getScope()->addEvalMap($f);

        return $this;
    }
}