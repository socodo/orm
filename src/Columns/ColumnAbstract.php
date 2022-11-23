<?php

namespace Socodo\ORM\Columns;

use Socodo\ORM\Interfaces\ColumnInterface;

abstract class ColumnAbstract implements ColumnInterface
{
    /** @var string Column name. */
    protected string $name;
    /** @var int|null Column length. */
    protected ?int $length = null;

    /** @var mixed|null Default value. */
    protected mixed $default = null;

    /** @var bool Is nullable. */
    protected bool $nullable = false;

    /** @var bool Is primary. */
    protected bool $primary = false;

    /**
     * Constructor.
     *
     * @param string $name
     */
    public function __construct (string $name)
    {
        $this->setName($name);
    }

    /**
     * Get column name.
     *
     * @return string
     */
    public function getName (): string
    {
        return $this->name;
    }

    /**
     * Set column name.
     *
     * @param string $name
     * @return void
     */
    public function setName (string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get default value.
     *
     * @return mixed
     */
    public function getDefault (): mixed
    {
        return $this->default;
    }

    /**
     * Set default value.
     *
     * @param mixed $default
     * @return void
     */
    public function setDefault (mixed $default): void
    {
        $this->default = $default;
    }

    /**
     * Determine if the column is nullable.
     *
     * @return bool
     */
    public function isNullable (): bool
    {
        return $this->nullable;
    }

    /**
     * Set is the column nullable.
     *
     * @param bool $nullable
     * @return void
     */
    public function setNullable (bool $nullable): void
    {
        $this->nullable = $nullable;
    }

    /**
     * Determine if the column is set as primary.
     *
     * @return bool
     */
    public function isPrimary (): bool
    {
        return $this->primary;
    }

    /**
     * Set the column primary.
     *
     * @param bool $primary
     * @return void
     */
    public function setPrimary (bool $primary): void
    {
        $this->primary = $primary;
    }

    /**
     * Handle PHP compatible data to raw data.
     *
     * @param mixed $fromPHP
     * @return mixed
     */
    public function to (mixed $fromPHP): mixed
    {
        return $fromPHP;
    }

    /**
     * Handle raw data to PHP compatible data.
     *
     * @param mixed $fromDB
     * @return mixed
     */
    public function from (mixed $fromDB): mixed
    {
        return $fromDB;
    }
}