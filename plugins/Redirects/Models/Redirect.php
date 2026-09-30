<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Redirects\Models;

/**
 * @property int         $id
 * @property string      $source_path
 * @property string      $target_url
 * @property int         $status_code
 * @property int         $enabled
 * @property string|null $notes
 * @property int         $hit_count
 * @property string|null $last_hit_at
 * @property string|null $created_at
 * @property string|null $updated_at
 * @method self eq(string $field, mixed $value, string $operator = 'AND')
 * @method self isNull(string $field, string $operator = 'AND')
 * @method self order(string $field)
 * @method self select(string $field, string ...$fields)
 * @method self limit(int $limit)
 * @method self offset(int $offset)
 * @property int $cnt Aggregate alias from COUNT(*) selects
 */
class Redirect extends \Pubvana\Models\AbstractModel
{
    /**
     * @param \flight\database\DatabaseInterface|\PDO|\mysqli|null $pdo
     * @param array<string, mixed>                                 $config
     */
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'redirects', $config);
    }

    /**
     * @return self[]
     */
    public function allOrdered(): array
    {
        return (new self($this->getDatabaseConnection()))
            ->order('source_path ASC')
            ->findAll();
    }

    /**
     * Find a redirect by ID.
     *
     * @param int $id
     * @return self|null
     */
    public function findById(int $id): ?self
    {
        // Fresh instance: a reused $this would alias every find to the same object.
        $query = new self($this->getDatabaseConnection());
        $query->eq('id', $id)->find();
        return $query->isHydrated() ? $query : null;
    }

    /**
     * Find an enabled redirect matching the given source path.
     * Checks exact matches first, then wildcard patterns.
     *
     * @param string $sourcePath
     * @return array{redirect: self|null, captures: array<int, string>}
     */
    public function findActiveBySourcePath(string $sourcePath): array
    {
        // Try exact match first
        $query = new self($this->getDatabaseConnection());
        $query->eq('source_path', $sourcePath)
            ->eq('enabled', 1)
            ->find();

        if ($query->isHydrated()) {
            return ['redirect' => $query, 'captures' => []];
        }

        // No exact match, try wildcard patterns
        return $this->findWildcardMatch($sourcePath);
    }

    /**
     * Find a wildcard redirect that matches the given source path.
     * Wildcard patterns end with * and use $1, $2, etc. in target_url for captured groups.
     *
     * @param string $sourcePath
     * @return array{redirect: self|null, captures: array<int, string>}
     */
    private function findWildcardMatch(string $sourcePath): array
    {
        $allRedirects = $this->allOrdered();
        
        foreach ($allRedirects as $redirect) {
            if ((int) $redirect->enabled !== 1) {
                continue;
            }

            $pattern = (string) $redirect->source_path;
            if (!str_ends_with($pattern, '*')) {
                continue;
            }

            // Convert wildcard pattern to regex
            // /pvdocs/v2/* becomes ^/pvdocs/v2/(.*)$
            $regexPattern = '^' . preg_quote(substr($pattern, 0, -1), '/') . '(.*)$';
            
            if (preg_match('#' . $regexPattern . '#', $sourcePath, $matches)) {
                return ['redirect' => $redirect, 'captures' => $matches];
            }
        }

        return ['redirect' => null, 'captures' => []];
    }

    /**
     * Paginated redirects, newest first.
     *
     * @return array<int, self>
     */
    public function paginate(int $page = 1, int $perPage = 25): array
    {
        $query = new self($this->getDatabaseConnection());
        return $query->order('id DESC')
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->findAll();
    }

    /**
     * Count all redirects.
     *
     * @return int
     */
    public function countAll(): int
    {
        $query = new self($this->getDatabaseConnection());
        $result = $query->select('COUNT(*) as cnt')->find();
        return (int) $result->cnt;
    }
}
