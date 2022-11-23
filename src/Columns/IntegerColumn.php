<?php

namespace Socodo\ORM\Columns;

class IntegerColumn extends ColumnAbstract
{
    /** @var bool Is auto incremented. */
    protected bool $autoIncrement = false;

    /**
     * Determine if the column is set as AI field.
     *
     * @return bool
     */
    public function isAutoIncrement (): bool
    {
        return $this->autoIncrement;
    }

    /**
     * Set the column auto incremented.
     *
     * @param bool $autoIncrement
     * @return void
     */
    public function setAutoIncrement (bool $autoIncrement): void
    {
        $this->primary = true;
        $this->autoIncrement = $autoIncrement;
    }
}