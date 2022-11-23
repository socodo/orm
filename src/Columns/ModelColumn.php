<?php

namespace Socodo\ORM\Columns;

use Socodo\ORM\ColumnResolutionException;
use Socodo\ORM\Interfaces\ColumnInterface;
use Socodo\ORM\Model;

/**
 * @template T
 */
class ModelColumn extends IntegerColumn
{
    /** @var class-string<T> Model class name. */
    protected string $modelName;

    /**
     * Set model name.
     *
     * @param T $modelName
     * @return void
     */
    public function setModelName (string $modelName): void
    {
        if (!is_subclass_of($modelName, Model::class))
        {
            throw new ColumnResolutionException('');
        }

        $this->modelName = $modelName;
    }

    /**
     * Get model name.
     *
     * @return string
     */
    public function getModelName (): string
    {
        return $this->modelName;
    }

    /**
     * Handle PHP compatible data to raw data.
     *
     * @param mixed $fromPHP
     * @return mixed
     */
    public function to (mixed $fromPHP): mixed
    {
        /** @var ColumnInterface $primary */
        $primary = $this->modelName::getPrimaryColumn();
        return $fromPHP->{$primary->getName()};
    }

    /**
     * Handle raw data to PHP compatible data.
     *
     * @param mixed $fromDB
     * @return mixed
     */
    public function from (mixed $fromDB): mixed
    {
        return $this->modelName::from($fromDB);
    }
}