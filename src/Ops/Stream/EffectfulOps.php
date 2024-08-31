<?php

namespace Phunkie\Streams\Ops\Stream;

trait EffectfulOps
{
    public function evalMap($f)
    {
        return $this->getPull()->evalMap($f);
    }

    public function evalFilter($f)
    {
        return $this->getPull()->evalFilter($f);
    }

    public function evalFlatMap($f)
    {
        return $this->getPull()->evalFlatMap($f);
    }
}