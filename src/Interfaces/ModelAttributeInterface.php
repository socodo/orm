<?php

namespace Socodo\ORM\Interfaces;

use Socodo\ORM\Interfaces\ColumnInterface;

interface ModelAttributeInterface
{
    /**
     * Handle.
     *
     * @param ColumnInterface $column
     * @return void
     */
    public function handle (ColumnInterface $column): void;
}