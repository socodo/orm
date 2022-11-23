<?php

namespace Socodo\ORM;

use InvalidArgumentException;
use Socodo\ORM\Columns\ModelColumn;
use Socodo\ORM\Enums\QueryTypes;
use Socodo\ORM\Interfaces\ColumnInterface;

/**
 * @template T
 */
class Repository
{
    /** @var array<array<ColumnInterface>> Join columns. */
    protected static array $joins = [];

    /** @var class-string<T> Model class name. */
    protected string $modelClass;

    /**
     * Constructor.
     *
     * @param T $modelClass
     */
    public function __construct (string $modelClass)
    {
        if (!is_subclass_of($modelClass, Model::class))
        {
            throw new InvalidArgumentException('Socodo\ORM\Repository::__construct() Argument #1 ($modelClass) must be sub-class of Socodo\ORM\Model.');
        }

        $this->modelClass = $modelClass;
    }

    /**
     * Find a row with primary key from the model.
     *
     * @return ?T
     */
    public function get (mixed $primary): ?Model
    {
        $primaryKey = $this->modelClass::getPrimaryColumn()->getName();

        $query = $this->createBaseQuery(QueryTypes::Select);
        $query->setWhere([ $primaryKey => $primary ]);

        $db = DB::getInstance();
        $result = $db->queryThenFetch($query, $query->getBindings());
        if ($result === false)
        {
            return null;
        }

        return $this->modelClass::from($result);
    }

    /**
     * Create a base query instance.
     *
     * @param QueryTypes $type
     * @return Query
     */
    protected function createBaseQuery (QueryTypes $type): Query
    {
        $query = new Query();
        $query->setQueryType($type);
        $query->setTargetTable($this->modelClass::getTableName());
        $query->setTargetColumns(array_values(array_map(static function (ColumnInterface $column) {
            return $column->getName();
        }, $this->modelClass::getColumns())));

        if ($type == QueryTypes::Select)
        {
            $joins = $this->findJoinColumns();
            /** @var ModelColumn $join */
            foreach ($joins as $join)
            {
                /** @var class-string<Model> $joinModel */
                $joinModel = $join->getModelName();
                $joinPrimary = $joinModel::getPrimaryColumn();
                $query->addJoin($joinModel::getTableName(), array_values(array_map(static function (ColumnInterface $column) {
                    return $column->getName();
                }, $joinModel::getColumns())), $joinPrimary->getName(), $join->getName());
            }
        }

        return $query;
    }

    /**
     * Find join columns from the model.
     *
     * @return array
     */
    protected function findJoinColumns (): array
    {
        if (!isset(static::$joins[$this->modelClass]))
        {
            $columns = $this->modelClass::getColumns();
            $joins = [];
            foreach ($columns as $column)
            {
                if ($column instanceof ModelColumn)
                {
                    $joins[] = $column;
                }
            }

            static::$joins[$this->modelClass] = $joins;
        }

        return static::$joins[$this->modelClass];
    }
}