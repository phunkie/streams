<?php

namespace Phunkie\Streams;

interface Showable
{
    public function toString(): string;
    public function showType(): string;
}
