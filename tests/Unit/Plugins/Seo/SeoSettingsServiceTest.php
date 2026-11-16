<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Seo;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Seo\Services\SeoSettingsService;
use Pubvana\Tests\Support\TestCase;

/**
 * SeoSettingsService over an in-memory settings stub.
 */
#[CoversClass(SeoSettingsService::class)]
final class SeoSettingsServiceTest extends TestCase
{
    /** @var array<string, mixed> */
    public array $stored = [];

    /** @var array<string, array<string, string>> */
    private array $crawlers = [
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->stored = [];
    }

    private function service(): SeoSettingsService
    {
        $test = $this;
        $crawlers = $this->crawlers;

        $app = $this->app([
            'settings' => static fn (): object => new class ($test) {
                public function __construct(private SeoSettingsServiceTest $test)
                {
                }

                public function get(string $key, mixed $default = null): mixed
                {
                    return $this->test->stored[$key] ?? $default;
                }

                public function set(string $key, mixed $value): void
                {
                    $this->test->stored[$key] = $value;
                }
            },
            'seoRobots' => static fn (): object => new class ($crawlers) {
                /**
                 * @param array<string, array<string, string>> $crawlers
                 */
                public function __construct(private array $crawlers)
                {
                }

                /**
                 * @return array<string, array<string, string>>
                 */
                public function getAiCrawlerList(): array
                {
                    return $this->crawlers;
                }
            },
        ]);

        return new SeoSettingsService($app);
    }

    public function testAllReturnsDefaults(): void
    {
        $settings = $this->service()->all();

        self::assertSame('|', $settings['title_separator']);
        self::assertSame('{title} {sep} {site_name}', $settings['title_template']);
        self::assertSame('en', $settings['default_language']);
        self::assertTrue($settings['sitemap_include_pages']);
        self::assertTrue($settings['ai_disclosure_enabled']);
        self::assertSame([], $settings['social_profiles']);
    }

    public function testAllReadsStoredValues(): void
    {
        $this->stored = [
            'Seo.title_separator'          => '~',
            'Seo.sitemap_include_archives' => false,
            'Seo.social_profiles'          => ['https://a.test'],
        ];

        $settings = $this->service()->all();

        self::assertSame('~', $settings['title_separator']);
        self::assertFalse($settings['sitemap_include_archives']);
        self::assertSame(['https://a.test'], $settings['social_profiles']);
    }

    public function testApiSaveWritesOnlySentKeys(): void
    {
        $written = $this->service()->saveApi([
            'title_separator'      => ' - ',
            'ai_disclosure_enabled' => 'false',
        ]);

        self::assertSame(['title_separator', 'ai_disclosure_enabled'], $written);
        self::assertSame('-', $this->stored['Seo.title_separator']);
        self::assertFalse($this->stored['Seo.ai_disclosure_enabled']);
        self::assertArrayNotHasKey('Seo.sitemap_include_pages', $this->stored);
    }

    public function testApiSaveCapsStringLength(): void
    {
        $this->service()->saveApi(['title_separator' => str_repeat('x', 40)]);

        self::assertSame(str_repeat('x', 10), $this->stored['Seo.title_separator']);
    }

    public function testApiSaveExpandsSocialProfilesFromString(): void
    {
        $this->service()->saveApi(['social_profiles' => " https://a.test \nhttps://b.test, ,"]);

        self::assertSame(['https://a.test', 'https://b.test'], $this->stored['Seo.social_profiles']);
    }

    public function testApiSaveAcceptsSocialProfilesArray(): void
    {
        $this->service()->saveApi(['social_profiles' => ['https://a.test', '  ', 42]]);

        self::assertSame(['https://a.test', '42'], $this->stored['Seo.social_profiles']);
    }

    public function testApiSaveRestrictsCrawlersToKnownBots(): void
    {
        $written = $this->service()->saveApi([
            'ai_crawlers' => [
                'GPTBot'         => 'allow',
                'Bogus-Bot'      => 'block',
                'PerplexityBot'  => 'maybe',
            ],
        ]);

        self::assertSame(['ai_crawlers'], $written);
        self::assertSame('allow', $this->stored['Seo.ai_crawler_gptbot']);
        self::assertArrayNotHasKey('Seo.ai_crawler_bogus_bot', $this->stored);
        self::assertArrayNotHasKey('Seo.ai_crawler_perplexitybot', $this->stored);
    }

    public function testApiSaveIgnoresUnknownKeys(): void
    {
        $written = $this->service()->saveApi(['not_a_setting' => 'x', 'aiApiPrefix' => '/nope']);

        self::assertSame([], $written);
        self::assertSame([], $this->stored);
    }

    public function testApiSaveAcceptsFormBooleanValues(): void
    {
        $this->service()->saveApi([
            'sitemap_include_pages'    => 'on',
            'sitemap_include_posts'    => '0',
            'sitemap_include_archives' => true,
            'llms_txt_include_pages'   => 'yes',
            'llms_txt_include_posts'   => 'no',
            'ai_disclosure_enabled'    => 1,
        ]);

        self::assertTrue($this->stored['Seo.sitemap_include_pages']);
        self::assertFalse($this->stored['Seo.sitemap_include_posts']);
        self::assertTrue($this->stored['Seo.sitemap_include_archives']);
        self::assertTrue($this->stored['Seo.llms_txt_include_pages']);
        self::assertFalse($this->stored['Seo.llms_txt_include_posts']);
        self::assertTrue($this->stored['Seo.ai_disclosure_enabled']);
    }

    public function testAdminFormWritesUntickedBooleansFalse(): void
    {
        $this->service()->saveAdminForm(['title_separator' => '-']);

        self::assertSame('-', $this->stored['Seo.title_separator']);
        self::assertFalse($this->stored['Seo.sitemap_include_pages']);
        self::assertFalse($this->stored['Seo.ai_disclosure_enabled']);
    }

    public function testAdminFormTreatsZeroAsFalse(): void
    {
        $this->service()->saveAdminForm(['sitemap_include_pages' => '0']);

        self::assertFalse($this->stored['Seo.sitemap_include_pages']);
    }

    public function testAdminFormKeepsAnyCrawlerNameAndRawStrings(): void
    {
        $this->service()->saveAdminForm([
            'title_separator' => ' - ',
            'ai_crawlers'     => ['Bogus-Bot' => 'allow'],
        ]);

        self::assertSame(' - ', $this->stored['Seo.title_separator']);
        self::assertSame('allow', $this->stored['Seo.ai_crawler_bogus_bot']);
    }

    public function testDescribeCoversEverySetting(): void
    {
        $service = $this->service();
        $described = $service->describe();

        foreach (array_keys($service->all()) as $key) {
            self::assertArrayHasKey($key, $described);
        }

        self::assertArrayHasKey('ai_crawlers', $described);
        self::assertSame('string', $described['title_separator']['type']);
        self::assertSame('boolean', $described['ai_disclosure_enabled']['type']);
    }
}
