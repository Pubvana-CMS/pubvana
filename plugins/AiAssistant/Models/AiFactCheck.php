<?php

declare(strict_types=1);

namespace Pubvana\Plugins\AiAssistant\Models;

/**
 * AiFactCheck - Stored fact-check report (ai_fact_checks table).
 *
 * One row per submitted fact check. Reports form a history per content
 * item: resubmitting adds a new row, nothing is replaced. Content identity
 * is snapshotted (title, slug, updated_at) so the history survives content
 * edits and the staleness check can compare the snapshot against the live
 * row. The submitting key is snapshotted by id and name so the trail
 * survives key deletion.
 *
 * Schema:
 *   id                 - Auto-increment primary key
 *   content_type       - 'post' or 'page'
 *   content_id         - Post or page id
 *   content_title      - Snapshot of the content title at check time
 *   content_slug       - Snapshot of the content slug at check time
 *   content_updated_at - Snapshot of the content updated_at at check time
 *   summary            - The findings prose (facts vs opinion, evidence)
 *   overall_verdict    - supported|partially_supported|refuted|unverifiable
 *   claim_count        - Number of claims in the claims JSON
 *   claims             - JSON array of claim rows
 *   prompt_version     - Version of the fact-checking prompt attested to
 *   prompt_interference- Whether the checked content tried to steer the check
 *   interference_note  - Quoted interference attempt, when flagged
 *   key_id             - FK to ai_keys.id
 *   key_name           - Snapshot of the key name (survives key deletion)
 *   created_at         - Submission timestamp
 *   updated_at         - Last update timestamp
 *
 * @package Pubvana\Plugins\AiAssistant\Models
 *
 * @property int            $id
 * @property string         $content_type      post|page
 * @property int            $content_id
 * @property string         $content_title
 * @property string         $content_slug
 * @property string|null    $content_updated_at
 * @property string         $summary
 * @property string         $overall_verdict
 * @property int            $claim_count
 * @property string         $claims            JSON array
 * @property string         $prompt_version
 * @property int            $prompt_interference 0|1
 * @property string|null    $interference_note
 * @property int|null       $key_id
 * @property string|null    $key_name
 * @property string|null    $created_at
 * @property string|null    $updated_at
 *
 * @method self eq(string $field, mixed $value, string $operator = 'AND')
 * @method self order(string $field)
 * @method self select(string $field, string ...$fields)
 * @method self limit(int $limit)
 * @method self offset(int $offset)
 */
class AiFactCheck extends \Pubvana\Models\AbstractModel
{
    /**
     * @param \flight\database\DatabaseInterface|\PDO|\mysqli|null $pdo
     * @param array<string, mixed>                                 $config
     */
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'ai_fact_checks', $config);
    }

    public function findById(int $id): ?self
    {
        $this->reset();
        $this->eq('id', $id)->find();
        return $this->isHydrated() ? $this : null;
    }

    /**
     * Newest report for one content item.
     */
    public function latestForContent(string $contentType, int $contentId): ?self
    {
        $model = new self($this->getDatabaseConnection());
        $model->eq('content_type', $contentType)
            ->eq('content_id', $contentId)
            ->order('id DESC')
            ->limit(1)
            ->find();
        return $model->isHydrated() ? $model : null;
    }

    /**
     * The decoded claims list, always a list of arrays.
     *
     * @return array<int, array<string, mixed>>
     */
    public function claimsArray(): array
    {
        $decoded = json_decode((string) $this->claims, true);
        if (!is_array($decoded)) {
            return [];
        }
        $claims = [];
        foreach ($decoded as $claim) {
            if (is_array($claim)) {
                $claims[] = $claim;
            }
        }
        return $claims;
    }
}
