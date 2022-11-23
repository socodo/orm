<?php

namespace Socodo\ORM\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Table
{
    protected string $tableName;

    /**
     * Constructor.
     *
     * @param string $tableName
     */
    public function __construct (string $tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * Get table name.
     *
     * @return string
     */
    public function getTableName (): string
    {
        return $this->tableName;
    }
}