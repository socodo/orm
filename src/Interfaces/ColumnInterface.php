<?php

namespace Socodo\ORM\Interfaces;

interface ColumnInterface
{
    /**
     * Get column name.
     *
     * @return string
     */
    public function getName (): string;

    /**
     * Set column name.
     *
     * @param string $name
     * @return void
     */
    public function setName (string $name): void;

    /**
     * Get default value.
     *
     * @return mixed
     */
    public function getDefault (): mixed;

    /**
     * Set default value.
     *
     * @param mixed $default
     * @return void
     */
    public function setDefault (mixed $default): void;

    /**
     * Determine if the column is nullable.
     *
     * @return bool
     */
    public function isNullable (): bool;

    /**
     * Set is the column nullable.
     *
     * @param bool $nullable
     * @return void
     */
    public function setNullable (bool $nullable): void;

    /**
     * Determine if the column is set as primary.
     *
     * @return bool
     */
    public function isPrimary (): bool;

    /**
     * Set the column primary.
     *
     * @param bool $primary
     * @return void
     */
    public function setPrimary (bool $primary): void;

    /**
     * Get property name.
     *
     * @return string
     */
    public function getBoundProperty (): string;

    /**
     * Set property name.
     *
     * @param string $propertyName
     * @return void
     */
    public function setBoundProperty (string $propertyName): void;

    /**
     * Handle PHP compatible data to raw data.
     *
     * @param mixed $fromPHP
     * @return mixed
     */
    public function to (mixed $fromPHP): mixed;

    /**
     * Handle raw data to PHP compatible data.
     *
     * @param mixed $fromDB
     * @return mixed
     */
    public function from (mixed $fromDB): mixed;
}