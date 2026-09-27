<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams\Ops\Stream;

use Phunkie\Streams\Type\Stream;

/**
 * Text operations for streams of text chunks.
 *
 * @method \Phunkie\Streams\Type\Pull getPull()
 * @method int getBytes()
 */
trait TextOps
{
    /**
     * Turn a stream of text chunks into a stream of lines, however the chunks were cut.
     *
     * @return Stream
     */
    public function lines(): Stream
    {
        return new Stream($this->getPull()->lines(), $this->getBytes());
    }
}
