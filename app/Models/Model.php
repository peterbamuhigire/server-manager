<?php

/**
 * Base Model Class
 * Chwezi Server Manager
 */

class Model
{
    protected PDO $db;
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->db = db();
    }

    /**
     * Find record by ID
     * @param int $id
     * @return array|false
     */
    public function find(int $id)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Get all records
     * @param string|null $orderBy
     * @param string $direction
     * @return array
     */
    public function all(?string $orderBy = null, string $direction = 'ASC'): array
    {
        $query = "SELECT * FROM {$this->table}";

        if ($orderBy) {
            $query .= " ORDER BY {$orderBy} {$direction}";
        }

        $stmt = $this->db->query($query);
        return $stmt->fetchAll();
    }

    /**
     * Find records matching criteria
     * @param array $criteria
     * @return array
     */
    public function where(array $criteria): array
    {
        $conditions = [];
        $values = [];

        foreach ($criteria as $column => $value) {
            $conditions[] = "{$column} = ?";
            $values[] = $value;
        }

        $query = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $conditions);
        $stmt = $this->db->prepare($query);
        $stmt->execute($values);

        return $stmt->fetchAll();
    }

    /**
     * Find first record matching criteria
     * @param array $criteria
     * @return array|false
     */
    public function whereFirst(array $criteria)
    {
        $conditions = [];
        $values = [];

        foreach ($criteria as $column => $value) {
            $conditions[] = "{$column} = ?";
            $values[] = $value;
        }

        $query = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $conditions) . " LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute($values);

        return $stmt->fetch();
    }

    /**
     * Create new record
     * @param array $data
     * @return int|false Insert ID or false on failure
     */
    public function create(array $data)
    {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $query = sprintf(
            "INSERT INTO {$this->table} (%s) VALUES (%s)",
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $stmt = $this->db->prepare($query);

        if ($stmt->execute(array_values($data))) {
            return (int)$this->db->lastInsertId();
        }

        return false;
    }

    /**
     * Update record
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $sets = [];
        $values = [];

        foreach ($data as $column => $value) {
            $sets[] = "{$column} = ?";
            $values[] = $value;
        }

        $values[] = $id;

        $query = sprintf(
            "UPDATE {$this->table} SET %s WHERE {$this->primaryKey} = ?",
            implode(', ', $sets)
        );

        $stmt = $this->db->prepare($query);
        return $stmt->execute($values);
    }

    /**
     * Delete record
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Count records
     * @param array $criteria
     * @return int
     */
    public function count(array $criteria = []): int
    {
        if (empty($criteria)) {
            $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table}");
            return (int)$stmt->fetchColumn();
        }

        $conditions = [];
        $values = [];

        foreach ($criteria as $column => $value) {
            $conditions[] = "{$column} = ?";
            $values[] = $value;
        }

        $query = "SELECT COUNT(*) FROM {$this->table} WHERE " . implode(' AND ', $conditions);
        $stmt = $this->db->prepare($query);
        $stmt->execute($values);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Check if record exists
     * @param array $criteria
     * @return bool
     */
    public function exists(array $criteria): bool
    {
        return $this->count($criteria) > 0;
    }

    /**
     * Execute raw query
     * @param string $query
     * @param array $params
     * @return PDOStatement
     */
    protected function query(string $query, array $params = []): PDOStatement
    {
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt;
    }
}
