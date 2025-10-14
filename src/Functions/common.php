<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

array_map(function ($file) {
    require_once $file;
}, glob(__DIR__ .'/*'));

const Pure = 'Pure';
const IO = 'IO';
