<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Bootstraps all function files in this directory.
 */
array_map(function ($file) {
    require_once $file;
}, glob(__DIR__ .'/*'));

/** @var string Effect type constant for pure (non-effectful) streams. */
const Pure = 'Pure';

/** @var string Effect type constant for IO (effectful) streams. */
const IO = 'IO';
