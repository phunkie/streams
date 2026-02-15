<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace {

    use Phunkie\Streams\Pull\PDOPull;
    use Phunkie\Streams\Type\Stream;

    /**
     * Create a Stream from a PDOStatement.
     *
     * The stream will yield rows as arrays (PDO::FETCH_ASSOC).
     *
     * @param \PDOStatement $stmt
     * @return Stream
     */
    function StreamFromPDO(\PDOStatement $stmt): Stream
    {
        return Stream(new PDOPull($stmt));
    }
}
