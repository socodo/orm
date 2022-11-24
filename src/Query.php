<?php

namespace Socodo\ORM;

use Socodo\ORM\Enums\QueryTypes;
use Socodo\ORM\Exceptions\QueryResolutionException;

class Query
{
    /** @var QueryTypes Query type. */
    protected QueryTypes $queryType;

    /** @var string Target table name. */
    protected string $targetTable;

    /** @var array<string> All target columns. */
    protected array $targetColumns = [];

    /** @var array Binding datasets. */
    protected array $bindings = [];

    /** @var ?string Where clause dataset. */
    protected ?string $where = null;

    /** @var array Join clause dataset. */
    protected array $joins = [];

    /** @var array Map of key where values are bound to. */
    protected array $valueKeyBounds = [];

    /** @var string|null Compiled query string, */
    protected ?string $compiledString = null;

    /**
     * Constructor.
     */
    public function __construct ()
    {

    }

    /**
     * Magic method to build as string.
     *
     * @return string
     */
    public function __toString (): string
    {
        if ($this->compiledString === null)
        {
            $this->compiledString = $this->buildQueryString();
        }

        return $this->compiledString;
    }

    /**
     * Build a query string.
     *
     * @return string
     */
    public function buildQueryString (): string
    {
        return match ($this->queryType)
        {
            QueryTypes::Select => $this->buildSelectQueryString(),
            QueryTypes::Insert => $this->buildInsertQueryString(),
            QueryTypes::Update => $this->buildUpdateQueryString(),
            QueryTypes::Upsert => $this->buildUpsertQueryString(),
            QueryTypes::Delete => $this->buildDeleteQueryString(),
        };
    }

    /**
     * Build query string for select query.
     *
     * @return string
     */
    protected function buildSelectQueryString (): string
    {
        $queryArr = [];
        $queryArr[] = 'SELECT';

        $targetTable = $this->targetTable;
        $columnArr = array_map(static function (string $column) use ($targetTable) {
            return $targetTable . '.' . $column . ' as ' . $targetTable . '___' . $column;
        }, $this->targetColumns);

        if (!empty($this->joins))
        {
            $joinArr = [];
            foreach ($this->joins as $join)
            {
                $joinTarget = $join['target'];
                $columnArr = array_merge($columnArr, array_map(static function (string $column) use ($joinTarget) {
                    return $joinTarget . '.' . $column . ' as ' . $joinTarget . '___' . $column;
                }, $join['targetColumns']));
                $joinArr[] = 'LEFT JOIN ' . $joinTarget . ' ON ' . $joinTarget . '.' . $join['targetSearchColumn'] . ' = ' . $join['fromSearchColumn'];
            }
        }

        $queryArr[] = implode(', ', $columnArr);
        $queryArr[] = 'FROM ' . $this->targetTable;

        if (isset($joinArr))
        {
            $queryArr = array_merge($queryArr, $joinArr);
        }

        if ($this->where !== null)
        {
            $queryArr[] = 'WHERE ' . $this->where;
        }

        return implode(' ', $queryArr);
    }

    protected function buildInsertQueryString (): string
    {
        return '';
    }

    protected function buildUpdateQueryString (): string
    {
        return '';
    }

    /**
     * Build query string for upsert query.
     *
     * @return string
     */
    protected function buildUpsertQueryString (): string
    {
        $queryArr = [];
        $queryArr[] = 'INSERT INTO';
        $queryArr[] = $this->targetTable;

        $columnArr = [];
        $valueArr = [];
        $updateArr = [];
        foreach ($this->valueKeyBounds as $key => $bound)
        {
            $columnArr[] = $key;
            $valueArr[] = $bound;
            $updateArr[] = $key . ' = ' . $bound;
        }

        $queryArr[] = '(' . implode(', ', $columnArr) . ')';
        $queryArr[] = 'VALUES';
        $queryArr[] = '(' . implode(', ', $valueArr) . ')';
        $queryArr[] = 'ON DUPLICATE KEY UPDATE';
        $queryArr[] = implode(', ', $updateArr);

        return implode(' ', $queryArr);
    }

    protected function buildDeleteQueryString (): string
    {
        return '';
    }

    /**
     * Get binding data array.
     *
     * @return array
     */
    public function getBindings (): array
    {
        return $this->bindings;
    }

    /**
     * Set query type.
     *
     * @param QueryTypes $type
     * @return void
     */
    public function setQueryType (QueryTypes $type): void
    {
        $this->queryType = $type;
    }

    /**
     * Set target table name.
     *
     * @param string $table
     * @return void
     */
    public function setTargetTable (string $table): void
    {
        $this->targetTable = $table;
    }

    /**
     * Set column list of target table.
     *
     * @param array $columns
     * @return void
     */
    public function setTargetColumns (array $columns): void
    {
        $this->targetColumns = $columns;
    }

    /**
     * Set where clause dataset.
     * Use [] for AND group, [[]] for OR group.
     *
     * @param array $where
     * @return void
     */
    public function setWhere (array $where): void
    {
        $queryType = $this->queryType;
        if ($queryType == QueryTypes::Insert)
        {
            throw new QueryResolutionException(static::class . '::setWhere() Cannot set where dataset on Insert typed query.');
        }

        $built = $this->buildWhere($where);
        $this->where = $built['query'];
        $this->bindings = array_merge($this->bindings, $built['bindings']);
    }

    /**
     * Build where dataset.
     *
     * @param array $where
     * @param bool $isAnd
     * @return array
     */
    protected function buildWhere (array $where, bool $isAnd = true): array
    {
        $queries = [];
        $bindings = [];

        if (isset($where[0]) && is_array($where[0]))
        {
            return $this->buildWhere($where[0], false);
        }

        foreach ($where as $key => $item)
        {
            if (is_array($item) && !str_ends_with($key, '~'))
            {
                $output = $this->buildWhere($item);
                $queries = array_merge($queries, $output['query']);
                $bindings = array_merge($queries, $output['bindings']);
                continue;
            }

            $operator = '=';

            if (str_ends_with($key, '!=') || str_ends_with($key, '>=') || str_ends_with($key, '<='))
            {
                $operator = substr($key, -2);
                $key = trim(substr($key, 0, -2));
            }

            else if (str_ends_with($key, '>') || str_ends_with($key, '<'))
            {
                $operator = substr($key, -1);
                $key = trim(substr($key, 0, -1));
            }

            else if (str_ends_with($key, '~'))
            {
                $operator = 'LIKE';
                $key = trim(substr($key, 0, -1));

                if (str_ends_with($key, '!'))
                {
                    $operator = 'NOT LIKE';
                    $key = trim(substr($key, 0, -1));
                }

                if (!str_starts_with($item, '%') && !str_ends_with($item, '%'))
                {
                    $item = '%' . $item . '%';
                }
            }

            if ($item !== null)
            {
                $binding = $this->getRandomBindingName('where', $key);
                $bindings[$binding] = $item;
            }
            else
            {
                if ($operator == '=')
                {
                    $operator = 'IS';
                }
                else
                {
                    $operator = 'IS NOT';
                }

                $binding = 'NULL';
            }

            $queries[] = $this->targetTable . '.' . $key . ' ' . $operator . ' ' . $binding;
        }

        $query = '(' . implode($isAnd ? ' AND ' : ' OR ', $queries) . ')';
        return [ 'query' => $query, 'bindings' => $bindings ];
    }

    /**
     * Get random binding name.
     *
     * @param string $type
     * @param string $key
     * @return string
     */
    protected function getRandomBindingName (string $type, string $key = ''): string
    {
        $rand = sha1(microtime());
        $rand = preg_replace('/[0-9]+/', '', $rand);
        return ':' . $type . '_' . $key . '_' . $rand;
    }

    /**
     * Add join clause dataset.
     *
     * @param string $target
     * @param array<string> $targetColumns
     * @param string $targetSearchColumn
     * @param string $fromSearchColumn
     * @return void
     */
    public function addJoin (string $target, array $targetColumns, string $targetSearchColumn, string $fromSearchColumn): void
    {
        $queryType = $this->queryType;
        if ($queryType != QueryTypes::Select)
        {
            throw new QueryResolutionException(static::class . '::addJoin() Cannot set join dataset on non Select typed query.');
        }

        $this->joins[] = [
            'target' => $target,
            'targetColumns' => $targetColumns,
            'targetSearchColumn' => $targetSearchColumn,
            'fromSearchColumn' => $fromSearchColumn
        ];
    }

    /**
     * Add value dataset.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function addValue (string $key, mixed $value): void
    {
        $queryType = $this->queryType;
        if ($queryType == QueryTypes::Select || $queryType == QueryTypes::Delete)
        {
            throw new QueryResolutionException(static::class . '::addValue() Cannot set value dataset on Select or Delete typed query.');
        }

        $bindingKey = $this->getRandomBindingName('value', $key);
        $this->bindings[$bindingKey] = $value;
        $this->valueKeyBounds[$key] = $bindingKey;
    }
}