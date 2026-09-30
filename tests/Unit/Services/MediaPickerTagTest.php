<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Services;

use flight\Engine;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Services\PluginView;
use Pubvana\Tests\Support\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * The {% media_picker %} Vision tag registered by PluginView.
 *
 * The tag's upload endpoint is not an argument: it comes from the render
 * data (`avatarUploadUrl`), so these tests render a real .tpl through
 * Vision with a fake media() facade and assert what the tag emitted.
 *
 * @package Pubvana\Tests\Unit\Services
 */
#[CoversClass(PluginView::class)]
final class MediaPickerTagTest extends TestCase
{
    private string $tmpRoot;

    /** @var list<array{field: string, value: string, url: string}> */
    public array $pickerCalls = [];

    /** @var Engine<object>|null */
    private ?Engine $engine = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpRoot = sys_get_temp_dir() . '/pubvana-mediapicker-' . uniqid();
        mkdir($this->tmpRoot . '/app-views', 0777, true);
        mkdir($this->tmpRoot . '/plugin-views', 0777, true);
        $this->pickerCalls = [];
    }

    protected function tearDown(): void
    {
        $this->deleteDir($this->tmpRoot);
        parent::tearDown();
    }

    public function testTagRendersThePickerWithTheUploadUrlFromRenderData(): void
    {
        $view = $this->view();
        $this->writeTemplate('plugin-views/form.tpl', "{% media_picker 'avatar' profile.avatar %}");

        $html = $view->fetch('pubvana/blog/form', [
            'profile' => ['avatar' => 'uploads/avatars/7.webp'],
            'avatarUploadUrl' => '/profile/7/avatar',
        ]);

        self::assertSame('PICKER', $html);
        self::assertSame([[
            'field' => 'avatar',
            'value' => 'uploads/avatars/7.webp',
            'url' => '/profile/7/avatar',
        ]], $this->pickerCalls);
    }

    public function testTagRendersNothingWithoutAnUploadUrl(): void
    {
        $view = $this->view();
        $this->writeTemplate('plugin-views/form.tpl', "{% media_picker 'avatar' profile.avatar %}");

        $html = $view->fetch('pubvana/blog/form', [
            'profile' => ['avatar' => 'uploads/avatars/7.webp'],
        ]);

        self::assertSame('', $html);
        self::assertSame([], $this->pickerCalls, 'no upload URL means no picker call');
    }

    public function testTagRendersNothingWhenMediaIsUnavailable(): void
    {
        $view = $this->view(mediaAvailable: false);
        $this->writeTemplate('plugin-views/form.tpl', "{% media_picker 'avatar' profile.avatar %}");

        $html = $view->fetch('pubvana/blog/form', [
            'profile' => ['avatar' => ''],
            'avatarUploadUrl' => '/profile/7/avatar',
        ]);

        self::assertSame('', $html);
    }

    public function testTagPassesAnEmptyValueWhenTheProfileHasNoAvatar(): void
    {
        $view = $this->view();
        $this->writeTemplate('plugin-views/form.tpl', "{% media_picker 'avatar' profile.avatar %}");

        $view->fetch('pubvana/blog/form', [
            'profile' => [],
            'avatarUploadUrl' => '/profile/7/avatar',
        ]);

        self::assertSame([[
            'field' => 'avatar',
            'value' => '',
            'url' => '/profile/7/avatar',
        ]], $this->pickerCalls);
    }

    // -----------------------------------------------------------------
    // Fixture helpers
    // -----------------------------------------------------------------

    private function view(bool $mediaAvailable = true): PluginView
    {
        $this->engine($mediaAvailable);
        $view = new PluginView($this->tmpRoot . '/app-views');
        $view->addPluginPath('pubvana/blog', $this->tmpRoot . '/plugin-views');

        return $view;
    }

    private function engine(bool $mediaAvailable): Engine
    {
        if ($this->engine === null) {
            $test = $this;
            $app = $this->app();
            $app->map('media', function () use ($test, $mediaAvailable): object {
                if (!$mediaAvailable) {
                    throw new \RuntimeException('Media plugin is disabled.');
                }

                return new class($test) {
                    public function __construct(private MediaPickerTagTest $t)
                    {
                    }

                    public function publicAvatarPicker(string $field, string $value, string $url): string
                    {
                        $this->t->pickerCalls[] = ['field' => $field, 'value' => $value, 'url' => $url];

                        return 'PICKER';
                    }
                };
            });
            \Flight::setEngine($app);
            $this->engine = $app;
        }

        return $this->engine;
    }

    private function writeTemplate(string $relative, string $body): void
    {
        $dir = $this->tmpRoot . '/' . dirname($relative);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($this->tmpRoot . '/' . $relative, $body);
    }

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
