<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Services;

use flight\Engine;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Models\Theme;
use Pubvana\Models\ThemeOption;
use Pubvana\Services\ThemeService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * ThemeService over the in-memory themes/theme_options tables with a
 * temp themes directory (getThemesPath overridden, the documented
 * test seam for the hardcoded PROJECT_ROOT path).
 *
 * Covers discover(), sync() reconciliation (insert, update, orphan
 * removal, disable/enable, default activation, option seeding),
 * activate() states, validateTheme() PHP-tag scanning, and the theme
 * option helpers.
 *
 * @package Pubvana\Tests\Unit\Services
 */
#[CoversClass(ThemeService::class)]
final class ThemeServiceTest extends TestCase
{
    private PDO $pdo;

    private string $tmpRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        $this->tmpRoot = sys_get_temp_dir() . '/pubvana-themes-' . uniqid();
        mkdir($this->tmpRoot, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->deleteDir($this->tmpRoot);
        parent::tearDown();
    }

    // -----------------------------------------------------------------
    // discover()
    // -----------------------------------------------------------------

    public function testDiscoverReadsManifestsAndFlagsBrokenJson(): void
    {
        $this->makeThemeDir('alpha', ['display_name' => 'Alpha', 'description' => 'A theme']);
        $this->makeThemeDir('plain', null); // no manifest -> skipped
        $this->makeThemeDir('broken', '{not json');

        $discovered = $this->service()->discover();

        self::assertCount(2, $discovered);

        $alpha = $this->pick($discovered, 'alpha');
        self::assertSame('Alpha', $alpha['display_name']);
        self::assertSame('A theme', $alpha['description']);
        self::assertSame('alpha', $alpha['folder']);
        self::assertNull($alpha['_disabled_reason']);

        $broken = $this->pick($discovered, 'broken');
        self::assertSame(
            "Invalid or unreadable pubvana.json in theme 'broken'.",
            $broken['_disabled_reason']
        );
    }

    // -----------------------------------------------------------------
    // sync()
    // -----------------------------------------------------------------

    public function testSyncInsertsNewThemesAndSeedsDefaultOptions(): void
    {
        $this->makeThemeDir('alpha', [
            'display_name' => 'Alpha',
            'provides'     => [
                'options' => [
                    'color'  => ['type' => 'text', 'default' => 'red'],
                    'layout' => ['type' => 'group', 'fields' => [
                        'sidebar' => ['type' => 'text', 'default' => 'right'],
                    ]],
                ],
            ],
        ]);

        $service = $this->service();
        $service->sync();

        $model = new Theme($this->pdo);
        $theme = $model->findByFolder('alpha');
        self::assertNotNull($theme);
        self::assertSame('Alpha', $theme->name);
        self::assertSame(0, (int) $theme->is_active, 'not auto-activated');

        $options = (new ThemeOption($this->pdo))->getForTheme((int) $theme->id);
        $map = [];
        foreach ($options as $row) {
            $map[$row->option_key] = $row->option_value;
        }
        self::assertSame('red', $map['color']);
        self::assertSame('right', $map['layout.sidebar'], 'group fields seed as key.field');
        self::assertSame(['alpha' => true], $service->getValidationResults());
    }

    public function testSyncUpdatesChangedMetadataAndReEnablesDisabledThemes(): void
    {
        $this->makeThemeDir('alpha', ['display_name' => 'Alpha', 'semver' => '1.0.0']);
        $service = $this->service();
        $service->sync();

        $model = new Theme($this->pdo);
        $row = $model->findByFolder('alpha');
        $row->disabled = 1;
        $row->disabled_reason = 'was broken';
        $row->save();

        // Manifest changed on disk, theme is fixed up.
        $this->makeThemeDir('alpha', ['display_name' => 'Alpha Two', 'semver' => '2.0.0']);
        $service->sync();

        $row = (new Theme($this->pdo))->findByFolder('alpha');
        self::assertSame('Alpha Two', $row->name);
        self::assertSame('2.0.0', $row->version);
        self::assertEmpty($row->disabled, 'a valid theme on disk is re-enabled');
        self::assertNull($row->disabled_reason);
    }

    public function testSyncDisablesThemesWithBrokenManifests(): void
    {
        $this->makeThemeDir('alpha', ['display_name' => 'Alpha']);
        $this->service()->sync();

        $this->makeThemeDir('alpha', 'not json');
        $this->service()->sync();

        $row = (new Theme($this->pdo))->findByFolder('alpha');
        self::assertSame(0, (int) $row->is_active, 'broken theme deactivated');
        self::assertSame(1, (int) $row->disabled);
        self::assertStringContainsString('Invalid or unreadable', (string) $row->disabled_reason);
    }

    public function testSyncRemovesOrphanedRowsForDeletedFolders(): void
    {
        $this->makeThemeDir('alpha', ['display_name' => 'Alpha']);
        $this->service()->sync();

        // Record without a folder on disk.
        $model = new Theme($this->pdo);
        $ghost = $model->findByFolder('ghost');
        self::assertNull($ghost);
        $ghost = new Theme($this->pdo);
        $ghost->name = 'Ghost';
        $ghost->folder = 'ghost';
        $ghost->is_active = 0;
        $ghost->insert();

        $this->service()->sync();

        self::assertNull((new Theme($this->pdo))->findByFolder('ghost'));
        self::assertNotNull((new Theme($this->pdo))->findByFolder('alpha'));
    }

    public function testSyncActivatesDefaultWhenNothingIsActive(): void
    {
        $this->makeThemeDir('default', ['display_name' => 'Default']);
        $this->makeThemeDir('alpha', ['display_name' => 'Alpha']);
        $this->service()->sync();

        self::assertSame('default', (new Theme($this->pdo))->findActive()->folder);
    }

    public function testSyncKeepsTheActiveTheme(): void
    {
        $this->makeThemeDir('alpha', ['display_name' => 'Alpha']);
        $service = $this->service();
        $service->sync();
        $alpha = (new Theme($this->pdo))->findByFolder('alpha');
        (new Theme($this->pdo))->activateById((int) $alpha->id);

        $service->sync();

        self::assertSame('alpha', (new Theme($this->pdo))->findActive()->folder);
    }

    public function testSyncSkipsDefaultActivationWhenNoDefaultExists(): void
    {
        $this->makeThemeDir('alpha', ['display_name' => 'Alpha']);
        $this->service()->sync();

        self::assertNull((new Theme($this->pdo))->findActive());
    }

    // -----------------------------------------------------------------
    // activate()
    // -----------------------------------------------------------------

    public function testActivateDemotesOthersAndPromotesTarget(): void
    {
        $this->makeThemeDir('default', ['display_name' => 'Default']);
        $this->makeThemeDir('alpha', ['display_name' => 'Alpha']);
        $service = $this->service();
        $service->sync();

        $alpha = (new Theme($this->pdo))->findByFolder('alpha');
        self::assertSame('activated', $service->activate((int) $alpha->id));

        $model = new Theme($this->pdo);
        self::assertSame('alpha', $model->findActive()->folder);
        self::assertSame('alpha', $service->getActive()->folder);
    }

    public function testActivateReturnsNotFoundForMissingId(): void
    {
        self::assertSame('not_found', $this->service()->activate(9999));
    }

    public function testActivateReturnsDisabledForDisabledThemes(): void
    {
        $this->makeThemeDir('alpha', ['display_name' => 'Alpha']);
        $this->service()->sync();
        $alpha = (new Theme($this->pdo))->findByFolder('alpha');
        $alpha->disabled = 1;
        $alpha->save();

        self::assertSame('disabled', $this->service()->activate((int) $alpha->id));
    }

    public function testActivateReturnsInvalidForThemesWithPhpTags(): void
    {
        $this->makeThemeDir('alpha', ['display_name' => 'Alpha']);
        mkdir($this->tmpRoot . '/alpha/Views', 0777, true);
        file_put_contents($this->tmpRoot . '/alpha/Views/layout.tpl', '<?php echo "x";');
        $this->service()->sync();
        $alpha = (new Theme($this->pdo))->findByFolder('alpha');

        self::assertSame('invalid', $this->service()->activate((int) $alpha->id));
        self::assertNull((new Theme($this->pdo))->findActive());
    }

    // -----------------------------------------------------------------
    // validateTheme()
    // -----------------------------------------------------------------

    public function testValidateThemeRejectsEveryPhpTagForm(): void
    {
        $cases = [
            '<?php echo 1;'  => 'long tag',
            '<?='            => 'short echo tag',
            '<% echo 1;'     => 'asp tag',
        ];

        foreach ($cases as $payload => $label) {
            $this->makeThemeDir('scanned', ['display_name' => 'S']);
            mkdir($this->tmpRoot . '/scanned/Views', 0777, true);
            file_put_contents($this->tmpRoot . '/scanned/Views/x.tpl', $payload);

            self::assertFalse($this->service()->validateTheme('scanned'), $label);

            $this->deleteDir($this->tmpRoot . '/scanned');
        }
    }

    public function testValidateThemeSkipsBinaryAndImageFiles(): void
    {
        $this->makeThemeDir('scanned', ['display_name' => 'S']);
        mkdir($this->tmpRoot . '/scanned/assets', 0777, true);
        // The PHP tag lives inside files whose extensions are skipped.
        file_put_contents($this->tmpRoot . '/scanned/assets/logo.png', '<?php');
        file_put_contents($this->tmpRoot . '/scanned/assets/font.woff', '<?php');

        self::assertTrue($this->service()->validateTheme('scanned'));
    }

    public function testValidateThemePassesCleanThemes(): void
    {
        $this->makeThemeDir('scanned', ['display_name' => 'S']);
        mkdir($this->tmpRoot . '/scanned/Views', 0777, true);
        file_put_contents($this->tmpRoot . '/scanned/Views/layout.tpl', '<html>{{ title }}</html>');

        self::assertTrue($this->service()->validateTheme('scanned'));
    }

    public function testValidateThemeFailsForMissingDirectories(): void
    {
        self::assertFalse($this->service()->validateTheme('missing'));
    }

    // -----------------------------------------------------------------
    // Theme options
    // -----------------------------------------------------------------

    public function testThemeOptionHelpersReadAndWritePerTheme(): void
    {
        $this->makeThemeDir('alpha', ['display_name' => 'Alpha']);
        $service = $this->service();
        $service->sync();
        $theme = (new Theme($this->pdo))->findByFolder('alpha');
        $themeId = (int) $theme->id;

        self::assertNull($service->getThemeOption($themeId, 'color'));
        self::assertSame('fallback', $service->getThemeOption($themeId, 'color', 'fallback'));

        $service->saveThemeOption($themeId, 'color', 'blue');

        self::assertSame('blue', $service->getThemeOption($themeId, 'color'));
        self::assertSame(['color' => 'blue'], $service->getThemeOptions($themeId));
        self::assertSame([], $service->getThemeOptions(9999));
    }

    public function testThemeOptionsAreMemoizedPerRequest(): void
    {
        $this->makeThemeDir('alpha', ['display_name' => 'Alpha']);
        $service = $this->service();
        $service->sync();
        $themeId = (int) (new Theme($this->pdo))->findByFolder('alpha')->id;

        self::assertSame([], $service->getThemeOptions($themeId));

        $this->insertOption($themeId, 'late', 'row');

        self::assertSame([], $service->getThemeOptions($themeId), 'served from the memo');
        self::assertSame(
            ['late' => 'row'],
            $this->freshService()->getThemeOptions($themeId)
        );
    }

    // -----------------------------------------------------------------
    // Fixture helpers
    // -----------------------------------------------------------------

    /**
     * ThemeService with getThemesPath pointed at the temp fixture root.
     */
    private function service(): ThemeService
    {
        $test = $this;

        $app = $this->app([
            'db' => fn(): PDO => $this->pdo,
        ]);

        return new class($app, $test->tmpRoot) extends ThemeService {
            public function __construct(Engine $app, private readonly string $themesRoot)
            {
                parent::__construct($app);
            }

            protected function getThemesPath(): string
            {
                return rtrim($this->themesRoot, '/') . '/';
            }
        };
    }

    private function freshService(): ThemeService
    {
        return $this->service();
    }

    /**
     * Create a theme fixture directory with a manifest (or raw JSON body).
     *
     * @param array<string, mixed>|string|null $manifest Array (json-encoded), raw string body, or null (no manifest)
     */
    private function makeThemeDir(string $folder, array|string|null $manifest): void
    {
        $dir = $this->tmpRoot . '/' . $folder;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        if (is_array($manifest)) {
            file_put_contents($dir . '/pubvana.json', (string) json_encode($manifest));
        } elseif (is_string($manifest)) {
            file_put_contents($dir . '/pubvana.json', $manifest);
        }
    }

    /**
     * Insert a theme option row directly.
     */
    private function insertOption(int $themeId, string $key, string $value): void
    {
        $option = new ThemeOption($this->pdo);
        $option->theme_id = $themeId;
        $option->option_key = $key;
        $option->option_value = $value;
        $option->insert();
    }

    /**
     * @param array<int, array<string, mixed>> $discovered
     * @return array<string, mixed>
     */
    private function pick(array $discovered, string $folder): array
    {
        foreach ($discovered as $info) {
            if (($info['folder'] ?? '') === $folder) {
                return $info;
            }
        }

        self::fail("Theme '{$folder}' was not discovered");
    }

    /**
     * Delete a directory tree.
     */
    private function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            $path = (string) $file->getPathname();
            is_dir($path) ? rmdir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
