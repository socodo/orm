<?php

namespace Socodo\ORM\Interfaces;

use Iterator;

interface ResultSetInterface extends Iterator
{
    /**
     * Get all rows.
     *
     * @return array<RowInterface>
     */
    public function all (): array;
}