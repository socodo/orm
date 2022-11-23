<?php

namespace Socodo\ORM\Columns;

class BoolColumn extends ColumnAbstract
{
    /**
     * Handle PHP compatible data to raw data.
     *
     * @param mixed $fromPHP
     * @return int
     */
    public function to (mixed $fromPHP): int
    {
        return $fromPHP ? 1 : 0;
    }

    /**
     * Handle raw data to PHP compatible data.
     *
     * @param mixed $fromDB
     * @return bool
     */
    public function from (mixed $fromDB): bool
    {
        return $fromDB === 1;
    }
}