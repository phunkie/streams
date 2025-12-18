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

class PDOPull implements Pull
{
    use CompileOps;
    use ShowOps;

    private $current;
    private int $key = 0;
    private Scope $scope;

    public function __construct(private PDOStatement $stmt)
    {
        $this->scope = new Scope();
    }

    public function pull()
    {
        return $this->current();
    }

    public function getScope(): Scope
    {
        return $this->scope;
    }

    public function toStream(): Stream
    {
        $stream = \Stream($this);
        $stream->setScope($this->getScope());

        return $stream;
    }

    public function setScope(Scope $scope): static
    {
        $this->scope = $scope;

        return $this;
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->current;
    }

    public function hasNext(): bool
    {
        return $this->valid();
    }

    // Iterator interface implementation
    public function rewind(): void
    {
        $this->key = 0;
        $this->next(); // Fetch first row
    }

    public function key(): int
    {
        return $this->key;
    }

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

    public function valid(): bool
    {
        return $this->current !== null;
    }

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
