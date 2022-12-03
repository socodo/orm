<?php

namespace Socodo\ORM;

use ArrayIterator;
use InvalidArgumentException;
use Iterator;
use Socodo\ORM\Columns\IntegerColumn;
use Socodo\ORM\Columns\ModelColumn;
use Socodo\ORM\Enums\QueryTypes;
use Socodo\ORM\Exceptions\RepositoryResolutionException;
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
            throw new InvalidArgumentException('Socodo\\ORM\\Repository::__construct() Argument #1 ($modelClass) must be sub-class of Socodo\ORM\Model.');
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
     * Find a row with conditions.
     *
     * @param array $where
     * @return ?T
     */
    public function findOne (array $where = []): ?Model
    {
        $query = $this->createBaseQuery(QueryTypes::Select);
        if (!empty($where))
        {
            $query->setWhere($where);
        }

        $db = DB::getInstance();
        $result = $db->queryThenFetch($query, $query->getBindings());
        if ($result === false)
        {
            return null;
        }

        return $this->modelClass::from($result);
    }

    /**
     * Find rows with conditions.
     *
     * @param array $where
     * @param bool $lazy
     * @return ModelIterator<T>
     */
    public function find (array $where = [], bool $lazy = true): ModelIterator
    {
        $query = $this->createBaseQuery(QueryTypes::Select);
        if (!empty($where))
        {
            $query->setWhere($where);
        }

        $db = DB::getInstance();
        $stmt = $db->query($query, $query->getBindings());
        if ($lazy)
        {
            $iterator = $stmt->getIterator();
        }
        else
        {
            $iterator = new ArrayIterator($db->fetchAll($stmt));
        }

        return new ModelIterator($this->modelClass, $iterator);
    }

    /**
     * Upsert the model.
     *
     * @param T|array<T>|ModelIterator<T> $model
     * @param bool $recursive
     * @return bool
     */
    public function save (array|ModelIterator|Model $model, bool $recursive = true): bool
    {
        $db = DB::getInstance();
        if (is_array($model) || $model instanceof ModelIterator)
        {
            $db->begin();
            foreach ($model as $m)
            {
                if ($this->save($m, $recursive) === false)
                {
                    $db->rollback();
                    return false;
                }
            }

            $db->commit();
            return true;
        }

        if ($recursive)
        {
            $db->begin();

            /** @var ModelColumn $column */
            foreach ($this->findJoinColumns() as $column)
            {
                if ($model->{$column->getBoundProperty()} !== null)
                {
                    /** @var Model $columnModel */
                    $columnModel = $column->getModelName();
                    $columnRepository = $columnModel::getRepository();
                    $result = $columnRepository->save($model->{$column->getBoundProperty()}, $recursive);
                    if ($result === false)
                    {
                        $db->rollback();
                        return false;
                    }
                }
            }
        }

        $primary = $model::getPrimaryColumn();
        if (!isset($model->{$primary->getBoundProperty()}) && !($primary instanceof IntegerColumn && $primary->isAutoIncrement()))
        {
            throw new RepositoryResolutionException('Socodo\\ORM\\Repository::write() Property $' . $primary->getBoundProperty() . ' for primary key "' . $primary->getName() . '" must be set.');
        }

        $query = $this->createBaseQuery(QueryTypes::Upsert);
        foreach ($this->modelClass::getColumns() as $column)
        {
            if (!$column instanceof ModelColumn)
            {
                $query->addValue($column->getName(), $model->{$column->getBoundProperty()} ?? $column->getDefault());
                continue;
            }

            /** @var Model $columnModel */
            if ($model->{$column->getBoundProperty()} !== null)
            {
                $columnModel = $column->getModelName();
                foreach ($columnModel::getColumns() as $innerColumn)
                {
                    if ($innerColumn->isPrimary())
                    {
                        $query->addValue($column->getName(), $model->{$column->getBoundProperty()}->{$innerColumn->getBoundProperty()});
                        break;
                    }
                }
            }
        }

        $result = $db->query($query, $query->getBindings());
        if ($result === false)
        {
            $recursive && $db->rollback();
            return false;
        }

        $primary = $this->modelClass::getPrimaryColumn()->getBoundProperty();
        if (!isset($model->{$primary}))
        {
            $model->{$primary} = $db->getLastInsertId();
        }

        $recursive && $db->commit();
        return true;
    }

    /**
     * Delete the model.
     *
     * @param array|ModelIterator|Model $model
     * @return bool
     */
    public function delete (array|ModelIterator|Model $model): bool
    {
        if (!is_array($model) && !($model instanceof ModelIterator))
        {
            $model = [ $model ];
        }

        $primaryColumn = null;
        $primaries = [];

        /** @var Model $m */
        foreach ($model as $m)
        {
            if ($primaryColumn === null)
            {
                $primaryColumn = $m::getPrimaryColumn();
            }

            $primaries[] = $m->{$primaryColumn->getBoundProperty()};
        }

        $query = $this->createBaseQuery(QueryTypes::Delete);
        $query->setWhere([ $primaryColumn->getName() . ' @' => $primaries ]);

        $db = DB::getInstance();
        $result = $db->query($query, $query->getBindings());
        return !!$result;
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
        $query->setTargetColumns(array_map(static function (ColumnInterface $column) {
            return $column->getName();
        }, $this->modelClass::getColumns()));

        if ($type == QueryTypes::Select)
        {
            /** @var ModelColumn $join */
            foreach ($this->findJoinColumns() as $join)
            {
                /** @var class-string<Model> $joinModel */
                $joinModel = $join->getModelName();
                $joinPrimary = $joinModel::getPrimaryColumn();
                $query->addJoin($joinModel::getTableName(), array_map(static function (ColumnInterface $column) {
                    return $column->getName();
                }, $joinModel::getColumns()), $joinPrimary->getName(), $join->getName());
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