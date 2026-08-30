<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Pages\Models;

/**
 * PageRevision - ActiveRecord model for the pages_revisions table.
 *
 * Snapshots a page before it is created or updated so an editor can
 * review and restore an earlier state. Mirrors the Blog plugin's
 * PostRevision pattern.
 *
 * @property int         $id
 * @property int         $page_id
 * @property int         $author_id
 * @property string      $title
 * @property string|null $content
 * @property string      $status
 * @property int         $allow_comments
 * @property string|null $created_at
 *
 * @package Pubvana\Plugins\Pages\Models
 */
class PageRevision extends \flight\ActiveRecord
{
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'pages_revisions', $config);
    }

    public int $id;
    public int $page_id;
    public int $author_id;
    public string $title;
    public ?string $content = null;
    public string $status = 'draft';
    public int $allow_comments = 0;
    public ?string $created_at = null;

    public function findById(int $id): ?self
    {
        $this->reset();
        $this->eq('id', $id)->find();
        return $this->isHydrated() ? $this : null;
    }

    public function getForPage(int $pageId): array
    {
        return (new self($this->getDatabaseConnection()))
            ->eq('page_id', $pageId)
            ->order('id DESC')
            ->findAll();
    }

    public function createFromPage(Page $page, int $authorId): self
    {
        $record = new self($this->getDatabaseConnection());
        $record->page_id          = (int) $page->id;
        $record->author_id        = $authorId;
        $record->title            = (string) $page->title;
        $record->content          = $page->content;
        $record->status           = $page->status;
        $record->allow_comments   = (int) $page->allow_comments;
        $record->created_at       = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $record->insert();

        return $record;
    }

    public function pruneForPage(int $pageId, int $max = 15): void
    {
        $all = (new self($this->getDatabaseConnection()))
            ->eq('page_id', $pageId)
            ->order('id DESC')
            ->findAll();

        if (count($all) <= $max) {
            return;
        }

        $toDelete = array_slice($all, $max);
        foreach ($toDelete as $revision) {
            $revision->delete();
        }
    }
}
