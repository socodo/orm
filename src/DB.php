<?php

namespace Socodo\ORM;

use PDO;
use PDOStatement;
use Socodo\ORM\Exceptions\DBResolutionException;

class DB
{
    /** @var DB Singleton instance. */
    protected static self $instance;

    /** @var PDO PDO instance. */
    protected PDO $pdo;

    /**
     * Get a singleton instance.
     *
     * @return DB
     * @throws DBResolutionException
     */
    public static function getInstance (): DB
    {
        if (!isset(static::$instance))
        {
            throw new DBResolutionException('wip: must be initialized first.');
        }

        return static::$instance;
    }

    /**
     * Constructor.
     *
     * @param string $host
     * @param string $name
     * @param string $user
     * @param string $password
     */
    public function __construct (string $host, string $name, string $user, string $password)
    {
        static::$instance = $this;
        $this->pdo = new PDO('mysql:host=' . $host . ';dbname=' . $name, $user, $password);
    }

    /**
     * Execute a query with binding params.
     *
     * @param string $query
     * @param array $bindings
     * @return PDOStatement
     */
    public function query (string $query, array $bindings = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($query);
        foreach ($bindings as $key => $value)
        {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt;
    }

    /**
     * Execute raw query.
     *
     * @param string $query
     * @return bool|PDOStatement
     */
    public function rawQuery (string $query): bool|PDOStatement
    {
        return $this->pdo->query($query);
    }

    /**
     * Fetch one result from a statement.
     *
     * @param PDOStatement $stmt
     * @return false|object
     */
    public function fetch (PDOStatement $stmt): false|object
    {
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Fetch all results from a statement.
     *
     * @param PDOStatement $stmt
     * @return array<object>|false
     */
    public function fetchAll (PDOStatement $stmt): false|array
    {
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Execute query then fetch the result.
     *
     * @param string $query
     * @param array $bindings
     * @return false|object
     */
    public function queryThenFetch (string $query, array $bindings = []): false|object
    {
        $stmt = $this->query($query, $bindings);
        return $this->fetch($stmt);
    }

    /**
     * Get a last insert id.
     *
     * @return bool|string
     */
    public function getLastInsertId (): bool|string
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Begin a new transaction.
     *
     * @return bool
     */
    public function begin (): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit a last transaction.
     *
     * @return bool
     */
    public function commit (): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Rollback a last transaction.
     *
     * @return bool
     */
    public function rollback (): bool
    {
        return $this->pdo->rollBack();
    }
}