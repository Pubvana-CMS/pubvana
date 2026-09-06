<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\AiAssistant;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\AiAssistant\Services\FactCheckService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * FactCheckService unit tests on the in-memory SQLite database.
 *
 * Covers the gate (terms, toggle, key check), the prompt fallback,
 * report validation and storage, staleness detection, and the panel and
 * block data shapes. The prompt is exercised through the bundled copy;
 * the remote fetch path is config-driven and stays untested here.
 */
#[CoversClass(FactCheckService::class)]
final class FactCheckServiceTest extends TestCase
{
    private PDO $pdo;

    /** URL the block provider's request stand-in reports. */
    private string $currentUrl = '/';

    /** Fresh settings store per test: no state leaks between tests. */
    private object $settings;

    protected function setUp(): void
    {
        $this->pdo = Sqlite::recreate();
        $this->createTables($this->pdo);
        $this->currentUrl = '/';
        $this->settings = new class {
            /** @var array<string, mixed> */
            public array $data = [];

            public function get(string $key, mixed $default = null): mixed
            {
                return $this->data[$key] ?? $default;
            }

            public function set(string $key, mixed $value): void
            {
                $this->data[$key] = $value;
            }
        };
    }

    // -----------------------------------------------------------------
    // Prompt and gate
    // -----------------------------------------------------------------

    public function testCurrentPromptFallsBackToBundledCopy(): void
    {
        $prompt = $this->service(['factcheck_prompt_url' => ''])->currentPrompt();

        self::assertSame('1.0.0', $prompt['version']);
        self::assertSame('bundled', $prompt['source']);
        self::assertNotSame('', $prompt['text']);
        self::assertSame(hash('sha256', $prompt['text']), $prompt['hash']);
    }

    public function testTermsNotCurrentUntilAccepted(): void
    {
        $service = $this->service();

        self::assertFalse($service->termsCurrent());
        self::assertNull($service->acceptedVersion());

        $service->acceptTerms();

        self::assertTrue($service->termsCurrent());
        self::assertSame('1.0.0', $service->acceptedVersion());
        self::assertNotNull($service->acceptedAt());
    }

    public function testEnableBlockedWithoutTermsAndKey(): void
    {
        $service = $this->service();

        self::assertFalse($service->setEnabled(true));
        self::assertFalse($service->isEnabled());
        self::assertCount(2, $service->enableBlockers());

        $this->insertKey(1);

        $blockers = $service->enableBlockers();
        self::assertCount(1, $blockers);
        self::assertStringContainsString('terms', $blockers[0]);

        $service->acceptTerms();
        self::assertSame([], $service->enableBlockers());

        self::assertTrue($service->setEnabled(true));
        self::assertTrue($service->isEnabled());
    }

    public function testEnableBlockedWithoutEnabledKey(): void
    {
        $service = $this->service();
        $service->acceptTerms();

        $this->insertKey(1, false);

        self::assertFalse($service->setEnabled(true));

        $this->pdo->exec('UPDATE ai_keys SET enabled = 1 WHERE id = 1');

        self::assertTrue($service->setEnabled(true));
    }

    public function testTurningOffNeedsNoGate(): void
    {
        $service = $this->service();
        $service->acceptTerms();
        $this->insertKey(1);
        $service->setEnabled(true);

        $service->setEnabled(false);

        self::assertFalse($service->isEnabled());
    }

    public function testGateStatusReflectsEachState(): void
    {
        $service = $this->service();

        self::assertFalse($service->gateStatus()['ok']);
        self::assertSame(403, $service->gateStatus()['code']);

        $service->acceptTerms();
        $this->insertKey(1);
        $service->setEnabled(true);

        self::assertTrue($service->gateStatus()['ok']);

        // An accepted version that no longer matches the live prompt
        // (simulating a prompt update) re-locks the gate with 409.
        $this->setSetting('Ai.factcheck_terms_version', '0.9');
        self::assertFalse($service->termsCurrent());
        self::assertSame(409, $service->gateStatus()['code']);
    }

    // -----------------------------------------------------------------
    // Report submission and validation
    // -----------------------------------------------------------------

    public function testSubmitReportStoresSnapshotAndCounts(): void
    {
        $service = $this->readyService();
        $this->insertPost(5);
        $this->insertKey(2);

        $key = (new \Pubvana\Plugins\AiAssistant\Models\AiKey($this->pdo))->findById(2);
        self::assertNotNull($key);

        $result = $service->submitReport('post', 5, $this->payload(), $key);

        self::assertTrue($result['ok'], $result['error']);
        $report = $result['report'];
        self::assertNotNull($report);

        $serialized = $service->serializeReport($report);
        self::assertSame('post', $serialized['content_type']);
        self::assertSame(5, $serialized['content_id']);
        self::assertSame('My Post', $serialized['content_title']);
        self::assertSame('my-post', $serialized['content_slug']);
        self::assertSame('2026-09-01 08:00:00', $serialized['content_updated_at']);
        self::assertSame('1.0.0', $serialized['prompt_version']);
        self::assertFalse($serialized['prompt_interference']);
        self::assertFalse($serialized['stale']);
        self::assertSame('test-key', $serialized['key_name']);
        self::assertSame(0, $serialized['counts']['supported']);
        self::assertSame(1, $serialized['counts']['partially_supported']);
        self::assertSame(1, $serialized['counts']['opinions']);
        self::assertSame('Partially supported', $serialized['overall_verdict_label']);
        self::assertCount(2, $serialized['claims']);
        self::assertSame('Partially supported', $serialized['claims'][0]['verdict_label']);
        self::assertSame(['https://example.com/a'], $serialized['claims'][0]['sources']);
        self::assertSame([], $serialized['claims'][1]['sources']);
        self::assertNotNull($serialized['claims'][1]['determination']);
        self::assertNull($serialized['claims'][1]['verdict']);
    }

    public function testSubmitRejectsMissingContent(): void
    {
        $service = $this->readyService();
        $result = $service->submitReport('post', 999, $this->payload());
        self::assertFalse($result['ok']);
        self::assertSame(404, $result['code']);
    }

    public function testSubmitRejectsDeletedContent(): void
    {
        $service = $this->readyService();
        $this->insertPost(6, true);

        $result = $service->submitReport('post', 6, $this->payload());

        self::assertFalse($result['ok']);
        self::assertSame(404, $result['code']);
    }

    public function testSubmitRejectsMissingPromptVersion(): void
    {
        $service = $this->readyService();
        $this->insertPost(5);

        $payload = $this->payload();
        unset($payload['prompt_version']);

        $result = $service->submitReport('post', 5, $payload);

        self::assertFalse($result['ok']);
        self::assertSame(422, $result['code']);
        self::assertStringContainsString('prompt_version', $result['error']);
    }

    public function testSubmitRejectsStalePromptVersion(): void
    {
        $service = $this->readyService();
        $this->insertPost(5);

        $payload = $this->payload();
        $payload['prompt_version'] = '0.9';

        $result = $service->submitReport('post', 5, $payload);

        self::assertFalse($result['ok']);
        self::assertSame(409, $result['code']);
    }

    public function testSubmitRejectsBadVerdictSummaryAndClaims(): void
    {
        $service = $this->readyService();
        $this->insertPost(5);

        $cases = [
            'summary'       => ['summary' => '   '],
            'verdict'       => ['overall_verdict' => 'kind-of-true'],
            'claims'        => ['claims' => 'not-a-list'],
            'claim-text'    => ['claims' => [['kind' => 'fact', 'verdict' => 'supported']]],
            'claim-verdict' => ['claims' => [['text' => 'x', 'kind' => 'fact', 'verdict' => 'maybe']]],
            'claim-kind'    => ['claims' => [['text' => 'x', 'kind' => 'hunch']]],
            'claim-sources' => ['claims' => [['text' => 'x', 'verdict' => 'supported', 'sources' => 'https://x.com']]],
        ];

        foreach ($cases as $case => $override) {
            $result = $service->submitReport('post', 5, array_merge($this->payload(), $override));
            self::assertFalse($result['ok'], "case '{$case}' unexpectedly passed");
            self::assertSame(422, $result['code'], "case '{$case}' wrong code");
        }
    }

    public function testSubmitRequiresInterferenceNoteWhenFlagged(): void
    {
        $service = $this->readyService();
        $this->insertPost(5);

        $payload = $this->payload();
        $payload['prompt_interference'] = true;

        $result = $service->submitReport('post', 5, $payload);
        self::assertFalse($result['ok']);
        self::assertSame(422, $result['code']);

        $payload['interference_note'] = 'The article embedded instructions to skip checking.';
        $result = $service->submitReport('post', 5, $payload);
        self::assertTrue($result['ok'], $result['error']);
        self::assertTrue($service->serializeReport($result['report'])['prompt_interference']);
    }

    public function testSubmitCapsSourcesAndClaims(): void
    {
        $service = $this->readyService();
        $this->insertPost(5);

        $payload = $this->payload();
        $payload['claims'] = [[
            'text'    => 'x',
            'verdict' => 'supported',
            'sources' => array_map(fn (int $i): string => 'https://example.com/' . $i, range(1, 12)),
        ]];
        $result = $service->submitReport('post', 5, $payload);
        self::assertFalse($result['ok']);
        self::assertStringContainsString('at most 10', $result['error']);

        $payload['claims'] = array_map(
            fn (int $i): array => ['text' => 'claim ' . $i, 'verdict' => 'supported'],
            range(1, 101)
        );
        $result = $service->submitReport('post', 5, $payload);
        self::assertFalse($result['ok']);
        self::assertStringContainsString('at most 100', $result['error']);
    }

    public function testSubmitWorksForPages(): void
    {
        $service = $this->readyService();
        $this->insertPage(3);

        $result = $service->submitReport('page', 3, $this->payload());

        self::assertTrue($result['ok'], $result['error']);
        self::assertSame('page', $service->serializeReport($result['report'])['content_type']);
    }

    // -----------------------------------------------------------------
    // History, listing, staleness
    // -----------------------------------------------------------------

    public function testResubmitsBuildHistoryWithLatestFirst(): void
    {
        $service = $this->readyService();
        $this->insertPost(5);

        $first = $service->submitReport('post', 5, $this->payload());
        self::assertTrue($first['ok'], $first['error']);

        $this->pdo->exec("UPDATE posts SET updated_at = '2026-09-02 10:00:00' WHERE id = 5");

        $second = $service->submitReport('post', 5, $this->payload());
        self::assertTrue($second['ok'], $second['error']);

        $latest = $service->latestReport('post', 5);
        self::assertNotNull($latest);
        self::assertSame((int) $second['report']->id, (int) $latest->id);
        self::assertSame(2, $service->countReports('post', 5));

        // The first report is now stale (content edited after the check).
        $stale = $service->serializeReport($service->findReport((int) $first['report']->id));
        self::assertTrue($stale['stale']);
        self::assertFalse($service->serializeReport($latest)['stale']);
    }

    public function testStaleWhenContentDeleted(): void
    {
        $service = $this->readyService();
        $this->insertPost(5);

        $result = $service->submitReport('post', 5, $this->payload());
        self::assertTrue($result['ok'], $result['error']);

        $this->pdo->exec("UPDATE posts SET deleted_at = '2026-09-02 10:00:00' WHERE id = 5");

        self::assertTrue($service->isStale($result['report']));
    }

    public function testListReportsFiltersAndPaginates(): void
    {
        $service = $this->readyService();
        $this->insertPost(5);
        $this->insertPage(3);

        $service->submitReport('post', 5, $this->payload());
        $service->submitReport('page', 3, $this->payload());
        $service->submitReport('post', 5, $this->payload());

        $all = $service->listReports();
        self::assertSame(3, $all['total']);
        self::assertCount(3, $all['items']);

        $posts = $service->listReports(1, 25, 'post', 5);
        self::assertSame(2, $posts['total']);
        self::assertCount(2, $posts['items']);

        $pageTwo = $service->listReports(2, 2);
        self::assertCount(1, $pageTwo['items']);
        self::assertSame(2, $pageTwo['page']);
    }

    public function testDeleteReport(): void
    {
        $service = $this->readyService();
        $this->insertPost(5);

        $result = $service->submitReport('post', 5, $this->payload());
        $id = (int) $result['report']->id;

        self::assertTrue($service->deleteReport($id));
        self::assertFalse($service->deleteReport($id));
        self::assertNull($service->findReport($id));
    }

    // -----------------------------------------------------------------
    // Panel and block data
    // -----------------------------------------------------------------

    public function testPanelDataStates(): void
    {
        $service = $this->readyService();
        $this->insertPost(5);

        self::assertSame('unsaved', $service->panelData('post', 0)['state']);
        self::assertSame('none', $service->panelData('post', 5)['state']);

        $service->submitReport('post', 5, $this->payload());

        $panel = $service->panelData('post', 5);
        self::assertSame('report', $panel['state']);
        self::assertTrue($panel['enabled']);
        self::assertSame('Partially supported', $panel['report']['overall_verdict_label']);
    }

    public function testPanelDataShowsOffStateWhenDisabled(): void
    {
        $service = $this->service();
        $this->insertPage(3);

        $panel = $service->panelData('page', 3);
        self::assertSame('none', $panel['state']);
        self::assertFalse($panel['enabled']);
    }

    public function testBlockDataHiddenWhenDisabled(): void
    {
        $service = $this->readyService();
        $this->insertPost(5);
        $service->submitReport('post', 5, $this->payload());

        $service->setEnabled(false);

        self::assertFalse($service->blockData([])['show']);
    }

    public function testBlockDataHiddenOffContentPages(): void
    {
        $service = $this->readyService();
        $this->insertPost(5);
        $service->submitReport('post', 5, $this->payload());

        $this->currentUrl = '/blog/category/news';
        self::assertFalse($service->blockData([])['show']);

        $this->currentUrl = '/';
        self::assertFalse($service->blockData([])['show']);
    }

    public function testBlockDataRendersForCurrentPost(): void
    {
        $service = $this->readyService();
        $this->insertPost(5);
        $service->submitReport('post', 5, $this->payload());

        $this->currentUrl = '/blog/my-post';
        $data = $service->blockData(['title' => 'Fact Check']);

        self::assertTrue($data['show']);
        self::assertSame('Fact Check', $data['title']);
        self::assertSame('Partially supported', $data['overall_verdict_label']);
        self::assertSame('https://pubvanacms.com/pages/fact-checking', $data['about_url']);
        self::assertFalse($data['stale']);
        self::assertFalse($data['interference']);
    }

    public function testBlockDataRendersForCurrentPage(): void
    {
        $service = $this->readyService();
        $this->insertPage(3);
        $service->submitReport('page', 3, $this->payload());

        $this->currentUrl = '/page/about';
        self::assertTrue($service->blockData([])['show']);

        $this->currentUrl = '/page/missing';
        self::assertFalse($service->blockData([])['show']);
    }

    public function testBlockDataFlagsStaleAndInterference(): void
    {
        $service = $this->readyService();
        $this->insertPost(5);

        $payload = $this->payload();
        $payload['prompt_interference'] = true;
        $payload['interference_note'] = 'Embedded instructions to skip checking.';
        $service->submitReport('post', 5, $payload);

        $this->currentUrl = '/blog/my-post';
        $data = $service->blockData([]);
        self::assertTrue($data['interference']);
        self::assertStringContainsString('skip checking', $data['interference_note']);

        $this->pdo->exec("UPDATE posts SET updated_at = '2026-09-02 10:00:00' WHERE id = 5");
        self::assertTrue($service->blockData([])['stale']);
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * A service wired against a stand-in engine: settings, the request
     * (reporting $this->currentUrl), the plugin loader, and the peer
     * content services the URL detection consults. The extra mappings
     * are inert for gate/report flows.
     */
    private function service(array $config = []): FactCheckService
    {
        $app = $this->app([
            'settings'     => fn () => $this->settingsStore(),
            'request'      => function () {
                $request = new \flight\net\Request();
                $request->url = $this->currentUrl;
                return $request;
            },
            'pluginLoader' => fn () => new class {
                public function routePrefix(string $plugin): string
                {
                    return $plugin === 'pubvana/blog' ? 'blog' : 'page';
                }
            },
            'blog' => fn () => new class {
                public function findPostBySlug(string $slug): ?object
                {
                    $statement = Sqlite::connection()->prepare(
                        "SELECT * FROM posts WHERE slug = :slug AND status = 'published' AND deleted_at IS NULL"
                    );
                    $statement->execute(['slug' => $slug]);
                    $row = $statement->fetch(PDO::FETCH_ASSOC);
                    return $row === false ? null : (object) $row;
                }
            },
            'pages' => fn () => new class {
                public function findPageBySlug(string $slug): ?object
                {
                    $statement = Sqlite::connection()->prepare(
                        "SELECT * FROM pages WHERE slug = :slug AND status = 'published' AND deleted_at IS NULL"
                    );
                    $statement->execute(['slug' => $slug]);
                    $row = $statement->fetch(PDO::FETCH_ASSOC);
                    return $row === false ? null : (object) $row;
                }
            },
        ]);

        return new FactCheckService($this->pdo, $app, $config);
    }
    /**
     * A service with terms accepted and at least one enabled key, so the
     * gate is open for report and block flows.
     */
    private function readyService(): FactCheckService
    {
        $service = $this->service();
        $service->acceptTerms();
        $this->insertKey(1);
        $service->setEnabled(true);
        return $service;
    }

    private function settingsStore(): object
    {
        return $this->settings;
    }

    private function setSetting(string $key, mixed $value): void
    {
        $this->settings->set($key, $value);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'prompt_version'  => '1.0.0',
            'summary'         => "The article mixes facts with opinion. Two of three factual claims hold up; one is refuted.\n\nDetermination: the closing paragraph is opinion.",
            'overall_verdict' => 'partially_supported',
            'claims'          => [
                [
                    'text'        => 'The bridge opened in 1937.',
                    'kind'        => 'fact',
                    'verdict'     => 'partially_supported',
                    'explanation' => 'Opened May 1937; the article says April.',
                    'correction'  => 'Opened in May 1937.',
                    'sources'     => ['https://example.com/a'],
                ],
                [
                    'text'          => 'The bridge is the prettiest anywhere.',
                    'kind'          => 'opinion',
                    'determination' => 'Opinion. Not checked as a factual claim.',
                ],
            ],
        ];
    }

    private function insertKey(int $id, bool $enabled = true): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO ai_keys (id, name, key_hash, key_prefix, enabled, failed_attempts)
             VALUES (:id, :name, :hash, :prefix, :enabled, 0)'
        );
        $statement->execute([
            'id'      => $id,
            'name'    => 'test-key',
            'hash'    => 'hash-' . $id,
            'prefix'  => 'pvai1_test',
            'enabled' => $enabled ? 1 : 0,
        ]);
    }

    private function insertPost(int $id, bool $deleted = false): void
    {
        $statement = $this->pdo->prepare(
            "INSERT INTO posts (id, title, slug, content, excerpt, status, author_id, views, is_featured,
                allow_comments, ai_generated, created_at, updated_at, deleted_at)
             VALUES (:id, 'My Post', 'my-post', '<p>Body</p>', null, 'published', 1, 0, 0, 1, 0,
                '2026-08-01 00:00:00', '2026-09-01 08:00:00', :deleted)"
        );
        $statement->execute([
            'id'      => $id,
            'deleted' => $deleted ? '2026-09-02 09:00:00' : null,
        ]);
    }

    private function insertPage(int $id): void
    {
        $statement = $this->pdo->prepare(
            "INSERT INTO pages (id, title, slug, content, status, created_by, created_at, updated_at, deleted_at)
             VALUES (:id, 'About', 'about', '<p>About</p>', 'published', 1, '2026-08-01 00:00:00',
                '2026-09-01 08:00:00', null)"
        );
        $statement->execute(['id' => $id]);
    }

    private function createTables(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE ai_fact_checks (
                id                  INTEGER PRIMARY KEY AUTOINCREMENT,
                content_type        TEXT NOT NULL,
                content_id          INTEGER NOT NULL,
                content_title       TEXT NOT NULL DEFAULT \'\',
                content_slug        TEXT NOT NULL DEFAULT \'\',
                content_updated_at  TEXT,
                summary             TEXT NOT NULL,
                overall_verdict     TEXT NOT NULL,
                claim_count         INTEGER NOT NULL DEFAULT 0,
                claims              TEXT NOT NULL,
                prompt_version      TEXT NOT NULL,
                prompt_interference INTEGER NOT NULL DEFAULT 0,
                interference_note   TEXT,
                key_id              INTEGER,
                key_name            TEXT,
                created_at          TEXT,
                updated_at          TEXT
            )'
        );

        $pdo->exec(
            'CREATE TABLE ai_keys (
                id              INTEGER PRIMARY KEY AUTOINCREMENT,
                name            TEXT NOT NULL,
                key_hash        TEXT NOT NULL UNIQUE,
                key_prefix      TEXT NOT NULL DEFAULT \'\',
                enabled         INTEGER NOT NULL DEFAULT 1,
                failed_attempts INTEGER NOT NULL DEFAULT 0,
                blocked_until   TEXT,
                last_used_at    TEXT,
                created_at      TEXT,
                updated_at      TEXT
            )'
        );

        $pdo->exec(
            'CREATE TABLE posts (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                title          TEXT NOT NULL,
                slug           TEXT NOT NULL,
                content        TEXT,
                excerpt        TEXT,
                status         TEXT NOT NULL DEFAULT \'draft\',
                author_id      INTEGER NOT NULL DEFAULT 1,
                views          INTEGER NOT NULL DEFAULT 0,
                is_featured    INTEGER NOT NULL DEFAULT 0,
                allow_comments INTEGER NOT NULL DEFAULT 1,
                ai_generated   INTEGER NOT NULL DEFAULT 0,
                created_at     TEXT,
                updated_at     TEXT,
                deleted_at     TEXT
            )'
        );

        $pdo->exec(
            'CREATE TABLE pages (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                title      TEXT,
                slug       TEXT NOT NULL,
                content    TEXT,
                status     TEXT NOT NULL DEFAULT \'draft\',
                created_by INTEGER NOT NULL DEFAULT 1,
                created_at TEXT,
                updated_at TEXT,
                deleted_at TEXT
            )'
        );
    }
}
