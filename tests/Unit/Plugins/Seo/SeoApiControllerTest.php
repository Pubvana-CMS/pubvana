<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Seo;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\AiAssistant\Services\AiService;
use Pubvana\Plugins\Seo\Controllers\SeoApiController;
use Pubvana\Plugins\Seo\Services\ContentAnalysisService;
use Pubvana\Plugins\Seo\Services\SeoSettingsService;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;
use flight\Engine;
use flight\util\Collection;

/**
 * jsonHalt() stops the request. This probe surfaces that so the assertions
 * can run against the state the request left behind.
 */
final class SeoApiHalt extends \RuntimeException
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(public array $payload, public int $status)
    {
        parent::__construct('api halt');
    }
}

/**
 * /api/ai/seo/* over the real AiService and the real SEO services.
 *
 * Auth, grants, and the audit log run for real against SQLite. Settings
 * use an in-memory store, and the crawler list comes from a stub so the
 * test does not need the whole settings table.
 */
#[CoversClass(SeoApiController::class)]
final class SeoApiControllerTest extends TestCase
{
    /** @var array<string, mixed> */
    private const AI_CONFIG = [
        'route_prefix'        => '/api/ai',
        'key_prefix'          => 'pvai1_',
        'max_failed_attempts' => 3,
        'block_minutes'       => 30,
        'log_limit'           => 200,
    ];

    private PDO $pdo;

    private string $token;

    private string $noGrantToken;

    /** @var \ArrayObject<string, mixed> */
    private \ArrayObject $settings;

    private ?string $envBackup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = Sqlite::recreate();
        $this->createTables($this->pdo);

        $this->envBackup = $_ENV['SESSION_ENCRYPTION_KEY'] ?? null;
        $_ENV['SESSION_ENCRYPTION_KEY'] = 'test-encryption-key-for-seo-api';
        putenv('SESSION_ENCRYPTION_KEY=test-encryption-key-for-seo-api');

        $this->settings = new \ArrayObject();
        $this->token = $this->makeKey(['seo.read', 'seo.manage']);
        $this->noGrantToken = $this->makeKey([]);
    }

    protected function tearDown(): void
    {
        if ($this->envBackup === null) {
            unset($_ENV['SESSION_ENCRYPTION_KEY']);
            putenv('SESSION_ENCRYPTION_KEY');
        } else {
            $_ENV['SESSION_ENCRYPTION_KEY'] = $this->envBackup;
            putenv('SESSION_ENCRYPTION_KEY=' . $this->envBackup);
        }

        parent::tearDown();
    }

    // -----------------------------------------------------------------
    // Auth and grants
    // -----------------------------------------------------------------

    public function testMissingTokenFails401(): void
    {
        $halt = $this->call('settings', [], null);

        self::assertSame(401, $halt->status);
        self::assertSame('error', $halt->payload['status']);
    }

    public function testSettingsNeedsReadGrant(): void
    {
        $halt = $this->call('settings', [], $this->noGrantToken);

        self::assertSame(403, $halt->status);
        self::assertSame('error', $halt->payload['status']);
    }

    public function testSaveNeedsManageGrant(): void
    {
        $readOnly = $this->makeKey(['seo.read']);

        $halt = $this->call('saveSettings', ['title_separator' => '-'], $readOnly);

        self::assertSame(403, $halt->status);
        self::assertFalse($this->settings->offsetExists('Seo.title_separator'));
    }

    public function testAnalyzeNeedsReadGrant(): void
    {
        $halt = $this->call('analyze', ['title' => 'Hello'], $this->noGrantToken);

        self::assertSame(403, $halt->status);
    }

    // -----------------------------------------------------------------
    // Help
    // -----------------------------------------------------------------

    public function testHelpIsOpenToAnyValidKey(): void
    {
        $halt = $this->call('help', [], $this->noGrantToken);

        self::assertSame(200, $halt->status);
        self::assertSame('ok', $halt->payload['status']);

        $data = (array) $halt->payload['data'];
        self::assertSame('/api/ai/seo', $data['base']);
        self::assertArrayHasKey('title_separator', (array) $data['fields']);
        self::assertArrayHasKey('GPTBot', (array) $data['crawlers']);
        self::assertNotEmpty($data['endpoints']);
        self::assertNotEmpty($data['rules']);
    }

    // -----------------------------------------------------------------
    // Settings
    // -----------------------------------------------------------------

    public function testSettingsReturnsValuesAndCrawlers(): void
    {
        $halt = $this->call('settings', [], $this->token);

        self::assertSame(200, $halt->status);

        $data = (array) $halt->payload['data'];
        $settings = (array) $data['settings'];
        self::assertSame('|', $settings['title_separator']);
        self::assertTrue($settings['ai_disclosure_enabled']);
        self::assertSame('block', ((array) $data['crawlers'])['GPTBot']['current']);
    }

    public function testSaveSettingsIsPartial(): void
    {
        $halt = $this->call('saveSettings', ['settings' => ['title_separator' => '~']], $this->token);

        self::assertSame(200, $halt->status);

        $data = (array) $halt->payload['data'];
        self::assertSame(['title_separator'], $data['saved']);
        self::assertSame('~', ((array) $data['settings'])['title_separator']);
        self::assertTrue(((array) $data['settings'])['sitemap_include_pages']);
        self::assertFalse($this->settings->offsetExists('Seo.sitemap_include_pages'));
    }

    public function testSaveSettingsAcceptsTheReadShape(): void
    {
        $halt = $this->call('saveSettings', [
            'settings' => [
                'organization_name'  => 'Example Co',
                'social_profiles'    => ['https://a.test'],
                'ai_disclosure_enabled' => false,
            ],
            'crawlers' => ['GPTBot' => 'allow'],
        ], $this->token);

        self::assertSame(200, $halt->status);

        self::assertSame('Example Co', $this->settings['Seo.organization_name']);
        self::assertSame(['https://a.test'], $this->settings['Seo.social_profiles']);
        self::assertFalse($this->settings['Seo.ai_disclosure_enabled']);
        self::assertSame('allow', $this->settings['Seo.ai_crawler_gptbot']);
    }

    public function testSaveSettingsRejectsEmptyPayload(): void
    {
        $halt = $this->call('saveSettings', [], $this->token);

        self::assertSame(422, $halt->status);
    }

    public function testSaveSettingsRejectsUnknownFields(): void
    {
        $halt = $this->call('saveSettings', ['nope' => 'x'], $this->token);

        self::assertSame(422, $halt->status);
    }

    // -----------------------------------------------------------------
    // Analyze
    // -----------------------------------------------------------------

    public function testAnalyzeReturnsScoreAndChecks(): void
    {
        $halt = $this->call('analyze', [
            'title'          => 'A useful guide to SEO',
            'content'        => '<p>A useful guide to SEO content.</p><h2>More SEO</h2>',
            'focus_keywords' => 'SEO, guide',
        ], $this->token);

        self::assertSame(200, $halt->status);

        $data = (array) $halt->payload['data'];
        self::assertIsInt($data['score']);
        self::assertNotEmpty($data['checks']);
    }

    public function testAnalyzeRejectsEmptyContent(): void
    {
        $halt = $this->call('analyze', ['title' => '  ', 'content' => ''], $this->token);

        self::assertSame(422, $halt->status);
    }

    // -----------------------------------------------------------------
    // Harness
    // -----------------------------------------------------------------

    /**
     * Run one controller action end to end and return the halt payload.
     *
     * @param array<string, mixed> $payload
     */
    private function call(string $action, array $payload, ?string $token): SeoApiHalt
    {
        $pdo = $this->pdo;
        $registry = $this->registry();
        $store = $this->settings;

        $app = $this->app([
            'request' => static fn (): object => new class ($token, $payload) {
                public string $method = 'POST';
                public string $url = '/api/ai/seo/test';
                public Collection $data;

                /**
                 * @param array<string, mixed> $payload
                 */
                public function __construct(private ?string $token, array $payload)
                {
                    $this->data = new Collection($payload);
                }

                public function getHeader(string $name): string
                {
                    return $name === 'Authorization' && $this->token !== null
                        ? 'Bearer ' . $this->token
                        : '';
                }
            },
            'settings' => static fn (): object => new class ($store) {
                /**
                 * @param \ArrayObject<string, mixed> $store
                 */
                public function __construct(private \ArrayObject $store)
                {
                }

                public function get(string $key, mixed $default = null): mixed
                {
                    return $this->store[$key] ?? $default;
                }

                public function set(string $key, mixed $value): void
                {
                    $this->store[$key] = $value;
                }
            },
            'seoRobots' => static fn (): object => new class {
                /**
                 * @return array<string, array<string, string>>
                 */
                public function getAiCrawlerList(): array
                {
                    return [
                        'GPTBot' => [
                            'bot'         => 'GPTBot',
                            'default'     => 'block',
                            'current'     => 'block',
                            'description' => 'OpenAI training',
                        ],
                        'PerplexityBot' => [
                            'bot'         => 'PerplexityBot',
                            'default'     => 'allow',
                            'current'     => 'allow',
                            'description' => 'Search answers',
                        ],
                    ];
                }
            },
            'adext' => static fn (): ExtensionRegistry => $registry,
        ]);

        $ai = new AiService($pdo, $app, self::AI_CONFIG);

        $app->map('ai', static fn (): AiService => $ai);
        $app->map('seoSettings', static fn (): SeoSettingsService => new SeoSettingsService($app));
        $app->map('seoAnalysis', static fn (): ContentAnalysisService => new ContentAnalysisService());
        $app->map('jsonHalt', static function (mixed $data, int $status = 200): never {
            throw new SeoApiHalt((array) $data, $status);
        });

        \Flight::setEngine($app);

        try {
            (new SeoApiController($app))->{$action}();
        } catch (SeoApiHalt $halt) {
            return $halt;
        }

        self::fail('The controller returned without halting.');
    }

    /**
     * @param list<string> $grants
     */
    private function makeKey(array $grants): string
    {
        $registry = $this->registry();
        $app = $this->app([
            'adext' => static fn (): ExtensionRegistry => $registry,
        ]);
        $ai = new AiService($this->pdo, $app, self::AI_CONFIG);
        $app->map('ai', static fn (): AiService => $ai);

        $created = $ai->createKey('SEO test key');
        $ai->updateGrants((int) $created['key']->id, $grants);

        return $created['plain'];
    }

    /**
     * The SEO plugin's own ai.grant registrations, which updateGrants() and
     * the help catalog read.
     */
    private function registry(): ExtensionRegistry
    {
        $registry = new ExtensionRegistry();
        $registry->register('ai.grant', 'default', [
            'seo.read' => [
                'group'   => 'seo',
                'label'   => 'View SEO settings',
                'summary' => 'Read the site SEO settings.',
                'method'  => 'GET',
                'path'    => '/api/ai/seo/settings',
            ],
            'seo.manage' => [
                'group'   => 'seo',
                'label'   => 'Manage SEO settings',
                'summary' => 'Change the site SEO settings.',
                'method'  => 'POST',
                'path'    => '/api/ai/seo/settings',
            ],
        ]);

        return $registry;
    }

    private function createTables(PDO $pdo): void
    {
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
            'CREATE TABLE ai_key_grants (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                key_id     INTEGER NOT NULL REFERENCES ai_keys(id) ON DELETE CASCADE,
                permission TEXT NOT NULL
            )'
        );
        $pdo->exec(
            'CREATE TABLE ai_logs (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                key_id      INTEGER,
                key_name    TEXT,
                method      TEXT NOT NULL,
                endpoint    TEXT NOT NULL,
                entity_type TEXT,
                entity_id   INTEGER,
                outcome     TEXT NOT NULL,
                detail      TEXT,
                ip          TEXT,
                created_at  TEXT
            )'
        );
    }
}
