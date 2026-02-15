<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams\Pull;

use PDOStatement;
use Phunkie\Streams\Ops\Pull\ResourcePull\CompileOps;
use Phunkie\Streams\Ops\Pull\ResourcePull\ShowOps;
use Phunkie\Streams\Type\Pull;
use Phunkie\Streams\Type\Scope;
use Phunkie\Streams\Type\Stream;

/**
 * Pull backed by a PDOStatement result set.
 *
 * Iterates over query results row-by-row, fetching each row as an
 * associative array (PDO::FETCH_ASSOC).
 */
class PDOPull implements Pull
{
    use CompileOps;
    use ShowOps;

    private $current;
    private int $key = 0;
    private Scope $scope;

    /**
     * @param PDOStatement $stmt An already-executed statement to iterate over.
     */
    public function __construct(private PDOStatement $stmt)
    {
        $this->scope = new Scope();
    }

    /**
     * Returns the current row without advancing.
     *
     * @return array<string, mixed>|null The current row as an associative array, or null if exhausted.
     */
    public function pull()
    {
        return $this->current();
    }

    /** Returns the current scope. */
    public function getScope(): Scope
    {
        return $this->scope;
    }

    /** Converts this pull into a Stream, preserving the current scope. */
    public function toStream(): Stream
    {
        $stream = \Stream($this);
        $stream->setScope($this->getScope());

        return $stream;
    }

    /** Replace the current scope. */
    public function setScope(Scope $scope): static
    {
        $this->scope = $scope;

        return $this;
    }

    /** Returns the current row. */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->current;
    }

    /** Alias for valid(); returns true while rows remain. */
    public function hasNext(): bool
    {
        return $this->valid();
    }

    /** Resets the key and fetches the first row. */
    public function rewind(): void
    {
        $this->key = 0;
        $this->next(); // Fetch first row
    }

    /** Returns the current row index (1-based after first next()). */
    public function key(): int
    {
        return $this->key;
    }

    /** Fetches the next row as an associative array, or sets current to null at end of results. */
    public function next(): void
    {
        $result = $this->stmt->fetch(\PDO::FETCH_ASSOC);
        if ($result === false) {
            $this->current = null;
        } else {
            $this->current = $result;
            $this->key++;
        }
    }

    /** Returns true while the current row is not null. */
    public function valid(): bool
    {
        return $this->current !== null;
    }

    /**
     * Consumes the remaining result set and returns all rows.
     *
     * If iteration has not started yet, rewinds first.
     *
     * @return array<int, array<string, mixed>> All remaining rows.
     */
    public function getValues(): array
    {
        $values = [];
        // Consumes the rest of the result set
        if ($this->current === null && $this->key === 0) {
            $this->rewind();
        }

        while ($this->valid()) {
            if ($this->current !== null) {
                $values[] = $this->current;
            }
            $this->next();
        }

        return $values;
    }
}
