<?php

namespace Socodo\ORM;

use Iterator;
use PDOStatement;

/**
 * @template T
 */
class ModelIterator implements Iterator
{
    /** @var class-string<T> Model name. */
    protected string $modelClass;

    /** @var Iterator PDO iterator. */
    protected Iterator $iterator;

    /**
     * Constructor.
     *
     * @param T $modelClass
     * @param Iterator $dataIterator
     */
    public function __construct (string $modelClass, Iterator $dataIterator)
    {
        $this->modelClass = $modelClass;
        $this->iterator = $dataIterator;
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