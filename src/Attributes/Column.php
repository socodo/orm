<?php

namespace Socodo\ORM\Attributes;

use Attribute;
use Socodo\ORM\Interfaces\ColumnInterface;
use Socodo\ORM\Interfaces\ModelAttributeInterface;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column implements ModelAttributeInterface
{
    /** @var string Column name. */
    protected string $name;

    /**
     * Constructor.
     *
     * @param string $name
     */
    public function __construct (string $name)
    {
        $this->name = $name;
    }

    /**
     * Handle.
     *
     * @param ColumnInterface $column
     * @return void
     */
    public function handle (ColumnInterface $column): void
    {
        $column->setName($this->name);
    }
}