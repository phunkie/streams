<?php

array_map(function ($file) {
    require_once $file;
}, glob(__DIR__ .'/*'));

const Pure = 'Pure';
const IO = 'IO';
