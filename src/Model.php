<?php

namespace Socodo\ORM;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionProperty;
use Socodo\ORM\Columns\StringColumn;
use Socodo\ORM\Exceptions\ColumnResolutionException;
use Socodo\ORM\Interfaces\ModelAttributeInterface;
use Socodo\ORM\Attributes\Table;
use Socodo\ORM\Columns\BoolColumn;
use Socodo\ORM\Columns\FloatColumn;
use Socodo\ORM\Columns\IntegerColumn;
use Socodo\ORM\Columns\ModelColumn;
use Socodo\ORM\Exceptions\ModelResolutionException;
use Socodo\ORM\Exceptions\PrimaryNotFoundException;
use Socodo\ORM\Interfaces\ColumnInterface;

class Model
{
    /** @var array<string,string> Table names. */
    protected static array $tableNames = [];

    /** @var array<string,array<ColumnInterface|array>> Column structures. */
    protected static array $columns = [];

    /** @var array<string,ColumnInterface> Primary columns. */
    protected static array $primaries = [];

    /**
     * Constructor.
     */
    public function __construct ()
    {

    }

    /**
     * Create an instance from a given data.
     *
     * @param array|object $data
     * @return static
     */
    public static function from (array|object $data): static
    {
        if (is_object($data))
        {
            $data = (array) $data;
        }

        $new = new static();
        foreach (static::getColumns() as $column)
        {
            $property = $column->getBoundProperty();
            $columnName = $column->getName();

            if (isset($data[static::getTableName() . '___' . $columnName]))
            {
                $data[$columnName] = $data[static::getTableName() . '___' . $columnName];
            }
            if (!isset($data[$columnName]))
            {
                if ($column instanceof IntegerColumn && $column->isAutoIncrement())
                {
                    continue;
                }

                if (!$column->getDefault() && !$column->isNullable())
                {
                    throw new ModelResolutionException(static::class . '::from() Column "' . $columnName . '" for property $' . $property . ' cannot be null.');
                }
                $data[$columnName] = $column->getDefault() ?? null;
            }

            try
            {
                if ($data[$columnName] === null && $column->isNullable())
                {
                    $new->{$property} = null;
                    continue;
                }
                if (!$column instanceof ModelColumn)
                {
                    $new->{$property} = $column->from($data[$columnName]);
                    continue;
                }
                $new->{$property} = $column->from($data);
            }
            catch (ModelResolutionException $e)
            {
                if ($column->isNullable())
                {
                    $new->{$property} = null;
                }
                throw $e;
            }
        }

        return $new;
    }

    /**
     * Get table name.
     *
     * @return string
     */
    public static function getTableName (): string
    {
        if (!isset(static::$tableNames[static::class]))
        {
            $class = new ReflectionClass(static::class);
            $attributes = $class->getAttributes(Table::class);
            if (empty($attributes))
            {
                throw new ModelResolutionException('Socodo\ORM\Model::getTableName() Cannot determine the table name of Model "' . static::class . '".');
            }

            /** @var Table $attr */
            $attr = $attributes[0]->newInstance();
            static::$tableNames[static::class] = $attr->getTableName();
        }

        return static::$tableNames[static::class];
    }

    /**
     * Get columns instances.
     *
     * @return array<ColumnInterface|array>
     * @throws ColumnResolutionException
     */
    public static function getColumns (): array
    {
        if (!isset(static::$columns[static::class]))
        {
            $class = new ReflectionClass(static::class);
            $properties = $class->getProperties(ReflectionProperty::IS_PUBLIC);

            $columns = [];
            foreach ($properties as $property)
            {
                $name = $property->getName();
                $snakeCasedName = strtolower(preg_replace([ '/([a-z0-9])([A-Z])/', '/([^_])([A-Z][a-z])/' ], '$1_$2', $name));

                $type = $property->getType();
                $typeName = $type->getName();
                $typeAllowsNull = $type->allowsNull();

                $column = match ($typeName)
                {
                    'int' => new IntegerColumn($snakeCasedName),
                    'double', 'float' => new FloatColumn($snakeCasedName),
                    'string' => new StringColumn($snakeCasedName),
                    'bool' => new BoolColumn($snakeCasedName),

                    default => (static function () use ($typeName, $snakeCasedName) {
                        if (is_subclass_of($typeName, Model::class))
                        {
                            $column = new ModelColumn($snakeCasedName);
                            $column->setModelName($typeName);
                            return $column;
                        }

                        throw new ModelResolutionException(static::class . '::getColumns() Cannot determine which ColumnInterface should be bound to property $' . $name . '.');
                    }) ()
                };

                $column->setBoundProperty($name);
                if ($typeAllowsNull)
                {
                    $column->setNullable(true);
                }

                $attributes = $property->getAttributes(ModelAttributeInterface::class, ReflectionAttribute::IS_INSTANCEOF);
                foreach ($attributes as $attribute)
                {
                    /** @var ModelAttributeInterface $attr */
                    $attr = $attribute->newInstance();
                    $attr->handle($column);
                }

                $columns[] = $column;
            }

            static::$columns[static::class] = $columns;
        }

        return static::$columns[static::class];
    }

    /**
     * Get primary column from the model.
     *
     * @return ColumnInterface
     */
    public static function getPrimaryColumn (): ColumnInterface
    {
        if (!isset(static::$primaries[static::class]))
        {
            $columns = static::getColumns();
            $primary = null;
            foreach ($columns as $column)
            {
                if ($column->isPrimary())
                {
                    $primary = $column;
                    break;
                }
            }

            if ($primary === null)
            {
                throw new PrimaryNotFoundException(static::class . '::getPrimaryColumn() Model does not have any primary column.');
            }

            static::$primaries[static::class] = $primary;
        }

        return static::$primaries[static::class];
    }
}