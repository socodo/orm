<?php

namespace Socodo\ORM\Attributes;

use Attribute;
use Socodo\ORM\Interfaces\ColumnInterface;
use Socodo\ORM\Interfaces\ModelAttributeInterface;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Primary implements ModelAttributeInterface
{
    /**
     * Handle.
     *
     * @param ColumnInterface $column
     * @return void
     */
    public function handle (ColumnInterface $column): void
    {
        $column->setPrimary(true);
    }
}