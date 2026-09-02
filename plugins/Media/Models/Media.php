<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Media\Models;

 /**
 * @property int         $id
 * @property string      $filename
 * @property string      $path
 * @property string      $mime_type
 * @property int         $size
 * @property string      $type          image|video|embed|...
 * @property string|null $title
 * @property string|null $alt_text
 * @property string|null $poster_path   Video poster image, relative to media path
 * @property string|null $embed_url
 * @property string|null $embed_provider
 * @property int|null    $uploaded_by   User id, null = uploaded by guest/CLI
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @method self eq(string $field, mixed $value, string $operator = 'AND')
 * @method self order(string $field)
 * @method self select(string $field, string ...$fields)
 * @method self limit(int $limit)
 * @method self offset(int $offset)
  *
 * @property int $cnt Aggregate alias from COUNT(*) selects
*/
class Media extends \Pubvana\Models\AbstractModel
{
    /**
     * @param \flight\database\DatabaseInterface|\PDO|\mysqli|null $pdo
     * @param array<string, mixed>                                 $config
     */
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'media', $config);
    }

    public function findById(int $id): ?self
    {
        $this->reset();
        $this->eq('id', $id)->find();

        return $this->isHydrated() ? $this : null;
    }

    /**
     * @return array<int, Media>
     */
    public function paginate(int $page = 1, int $perPage = 24, ?string $type = null): array
    {
        $query = new self($this->getDatabaseConnection());

        if ($type !== null) {
            $query->eq('type', $type);
        }

        return $query->order('id DESC')
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->findAll();
    }

    public function countAll(?string $type = null): int
    {
        $query = new self($this->getDatabaseConnection());
        $query->select('COUNT(*) as cnt');

        if ($type !== null) {
            $query->eq('type', $type);
        }

        $result = $query->find();

        return (int) $result->cnt;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createRecord(array $data): self
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        $media = new self($this->getDatabaseConnection());

        foreach ($data as $key => $value) {
            $media->$key = $value;
        }

        $media->created_at = $now;
        $media->updated_at = $now;
        $media->insert();

        return $media;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateMeta(array $data): void
    {
        $allowed = ['alt_text', 'title', 'poster_path'];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $this->$field = trim((string) $data[$field]) ?: null;
            }
        }

        $this->updated_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->save();
    }
}
