<?php

namespace App\Models;

use CodeIgniter\Model;

class BackupModel extends Model
{
    // No single table — this model wraps raw DB operations (schema introspection,
    // credentials, dump/restore). Only uses $this->db directly; never call find(),
    // insert(), or other built-in CRUD methods on this model.
    protected $table      = '';
    protected $returnType = 'object';

    /**
     * Get database connection credentials for shell commands (mysqldump/mysql).
     */
    public function getCredentials(): object
    {
        return (object) [
            'database' => $this->db->getDatabase(),
            'hostname' => $this->db->hostname,
            'username' => $this->db->username,
            'password' => $this->db->password,
            'port'     => $this->db->port ?? 3306,
        ];
    }

    /**
     * Get all table names in the current database.
     */
    public function getTableNames(): array
    {
        $tables = [];
        $result = $this->db->query('SHOW TABLES');
        foreach ($result->getResultArray() as $row) {
            $tables[] = reset($row);
        }
        return $tables;
    }

    /**
     * Get the CREATE TABLE DDL for a given table.
     */
    public function getCreateTable(string $table): string
    {
        $result = $this->db->query("SHOW CREATE TABLE `{$table}`");
        $row    = $result->getRowArray();
        return $row['Create Table'] ?? array_values($row)[1];
    }

    /**
     * Get all rows from a table as arrays.
     */
    public function getAllRows(string $table): array
    {
        return $this->db->query("SELECT * FROM `{$table}`")->getResultArray();
    }

    /**
     * Escape a value for SQL output.
     */
    public function escape($value): string
    {
        return $this->db->escape($value);
    }

    /**
     * Execute a raw SQL statement.
     */
    public function rawQuery(string $sql): void
    {
        $this->db->query($sql);
    }

    /**
     * Check whether a table exists in the database.
     */
    public function tableExists(string $table): bool
    {
        return $this->db->tableExists($table);
    }
}
