<?php

namespace Phunkie\Streams\Infinite;

interface Infinite
{
    public function getValues(): \Generator;
    public function reset(): void;
}