<?php

namespace Socodo\ORM;

use Iterator;
use PDOStatement;

/**
 * @template T
 */
class LazyModelIterator implements Iterator
{
    /** @var class-string<T> Model name. */
    protected string $modelClass;

    /** @var PDOStatement PDO statement. */
    protected PDOStatement $stmt;

    /** @var Iterator PDO iterator. */
    protected Iterator $iterator;

    /**
     * Constructor.
     *
     * @param T $modelClass
     * @param PDOStatement $stmt
     */
    public function __construct (string $modelClass, PDOStatement $stmt)
    {
        $this->modelClass = $modelClass;
        $this->stmt = $stmt;
        $this->iterator = $stmt->getIterator();
    }

    /**
     * Get current item.
     *
     * @return mixed
     */
    public function current (): mixed
    {
        $current = $this->iterator->current();
        return $this->modelClass::from($current);
    }

    /**
     * Move forward to next element.
     *
     * @return void
     */
    public function next (): void
    {
        $this->iterator->next();
    }

    /**
     * Get current key.
     *
     * @return mixed
     */
    public function key (): mixed
    {
        return $this->iterator->key();
    }

    /**
     * Determine if current position is valid.
     *
     * @return bool
     */
    public function valid (): bool
    {
        return $this->iterator->valid();
    }

    /**
     * Rewind to first position.
     *
     * @return void
     */
    public function rewind (): void
    {
        $this->iterator->rewind();
    }
}