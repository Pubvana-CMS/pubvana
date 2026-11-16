<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Analytics\Models;

use Pubvana\Models\AbstractModel;

/**
 * Shared database access for the analytics models.
 *
 * The model receives a PdoAdapter, not a PDO. The adapter's statement API
 * returns single columns or fills objects, and the report queries need rows.
 * The models pull the PDO out of the adapter and run prepared statements.
 *
 * @package Pubvana\Plugins\Analytics\Models
 */
abstract class AnalyticsModel extends AbstractModel
{
    /**
     * PDO held by the ActiveRecord connection.
     */
    protected function pdo(): \PDO
    {
        $connection = $this->getDatabaseConnection();

        if ($connection instanceof \flight\database\pdo\PdoAdapter) {
            return $connection->getConnection();
        }

        if ($connection instanceof \PDO) {
            return $connection;
        }

        throw new \RuntimeException('Analytics models require a PDO connection.');
    }

    /**
     * Run a statement and return every row.
     *
     * @param array<string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    protected function fetchRows(string $sql, array $params = []): array
    {
        $stmt = $this->pdo()->prepare($sql);
        if ($stmt === false) {
            throw new \RuntimeException('Analytics query could not be prepared.');
        }

        $stmt->execute($params);

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $rows;
    }

    /**
     * Run a statement and return one number.
     *
     * @param array<string, mixed> $params
     */
    protected function total(string $sql, array $params = []): int
    {
        $stmt = $this->pdo()->prepare($sql);
        if ($stmt === false) {
            throw new \RuntimeException('Analytics query could not be prepared.');
        }

        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Run a statement and return the affected row count.
     *
     * @param array<string, mixed> $params
     */
    protected function run(string $sql, array $params = []): int
    {
        $stmt = $this->pdo()->prepare($sql);
        if ($stmt === false) {
            throw new \RuntimeException('Analytics query could not be prepared.');
        }

        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * Run the callback inside a transaction on this model's connection.
     */
    protected function runInTransaction(callable $work): void
    {
        $pdo = $this->pdo();
        $pdo->beginTransaction();

        try {
            $work();
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
