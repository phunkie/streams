<?php

namespace Phunkie\Streams\Ops\Stream;

trait EffectfulOps
{
    public function evalMap($f)
    {
        $this->getPull()->getScope()->addCallable('evalMap', $f);

        return $this;
    }

    public function evalFilter($f)
    {
        $this->getPull()->getScope()->addCallable('evalFilter', $f);
    }

    public function evalFlatMap($f)
    {
        $this->getPull()->getScope()->addCallable('evalMap', $f);
    }
}