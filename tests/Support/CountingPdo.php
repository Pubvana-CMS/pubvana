<?php

declare(strict_types=1);

namespace Pubvana\Tests\Support;

use PDO;

/**
 * PDO that counts statements, for guarding against N+1 regressions.
 *
 * The ActiveRecord layer runs every query through prepare(), so wrapping
 * the connection is the cheapest way to get a query count that PDO does
 * not expose. copyOf() starts from another SQLite connection's schema and
 * keeps its own in-memory database, leaving the shared Sqlite connection
 * untouched.
 */
final class CountingPdo extends PDO
{
    /** Statements prepared, queried or executed on this connection. */
    public int $queries = 0;

    /**
     * In-memory connection carrying the schema of $source.
     *
     * Tables come first (the ORDER BY ranks them above indexes and
     * triggers), so replaying sqlite_master top to bottom always works.
     */
    public static function copyOf(PDO $source): self
    {
        $stmt = $source->query(
            "SELECT sql FROM sqlite_master WHERE sql IS NOT NULL"
            . " AND name NOT LIKE 'sqlite_%' ORDER BY (type = 'table') DESC"
        );
        if ($stmt === false) {
            throw new \RuntimeException('Could not read the schema to copy.');
        }

        /** @var list<string> $schema */
        $schema = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return new self($schema);
    }

    /**
     * @param list<string> $schema Statements run before counting starts
     */
    public function __construct(array $schema = [])
    {
        parent::__construct('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        parent::exec('PRAGMA foreign_keys = ON');

        foreach ($schema as $sql) {
            parent::exec($sql);
        }
    }

    public function prepare(string $query, array $options = []): \PDOStatement|false
    {
        $this->queries++;
        return parent::prepare($query, $options);
    }

    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): \PDOStatement|false
    {
        $this->queries++;
        return parent::query($query, $fetchMode, ...$fetchModeArgs);
    }

    public function exec(string $statement): int|false
    {
        $this->queries++;
        return parent::exec($statement);
    }
}
