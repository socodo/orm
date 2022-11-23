<?php

namespace Socodo\ORM\Attributes;

use Attribute;
use Socodo\ORM\ColumnResolutionException;
use Socodo\ORM\Columns\IntegerColumn;
use Socodo\ORM\Interfaces\ColumnInterface;
use Socodo\ORM\Interfaces\ModelAttributeInterface;

#[Attribute(Attribute::TARGET_PROPERTY)]
class AutoIncrement implements ModelAttributeInterface
{
    /**
     * Handle.
     *
     * @param ColumnInterface $column
     * @return void
     */
    public function handle (ColumnInterface $column): void
    {
        if (!$column instanceof IntegerColumn)
        {
            throw new ColumnResolutionException('Socodo\ORM\Attributes\AutoIncrement can decorate only IntegerColumn, but decorated ' . get_class($column) . '.');
        }

        $column->setAutoIncrement(true);
    }
}