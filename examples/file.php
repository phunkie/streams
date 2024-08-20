<?php

use function Phunkie\Streams\IO\File\readAll;

require_once dirname(dirname(__FILE__)) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/printLn.php';


readAll("large_file.txt", 1);
