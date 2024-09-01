<?php

namespace Phunkie\Streams\Ops\Stream;

trait EffectfulOps
{
    public function evalMap($f)
    {
        $this->getPull()->evalMap($f);

        return $this;
    }

    public function evalFilter($f)
    {
        $this->getPull()->evalFilter($f);

        return $this;
    }

    public function evalFlatMap($f)
    {
        $this->getPull()->getScope()->addCallable('evalMap', $f);

        return $this;
    }
}
