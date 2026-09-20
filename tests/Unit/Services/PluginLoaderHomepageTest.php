<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Services;

use flight\Engine;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Services\PluginLoader;
use Pubvana\Tests\Support\TestCase;

/**
 * Homepage dispatch coverage: which provider serves "/", decline order,
 * a failing provider, an unknown token, and the fall-through to the 404.
 *
 * Providers are real registrations on a real ExtensionRegistry, so this
 * exercises the same path a request takes without booting plugins or the
 * admin registry. The 404 seam is recorded rather than rendered.
 */
#[CoversClass(PluginLoader::class)]
final class PluginLoaderHomepageTest extends TestCase
{
    /** @var list<string> Providers whose callable ran, in order */
    public array $served = [];

    private ExtensionRegistry $adext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->served = [];
        $this->adext = new ExtensionRegistry();
    }

    public function testTheStoredTokenServesEvenWhenAnotherProviderOutranksIt(): void
    {
        $this->registerProvider('pubvana.blog', 'blog', 20, true);
        $this->registerProvider('pubvana.pages', 'page', 30, true);

        $loader = $this->loader(['CMS.homepageType' => 'page']);
        $loader->dispatchHomepage();

        self::assertSame(['pubvana.pages'], $this->served);
        self::assertSame(0, $loader->notFound);
    }

    public function testAnUnsetTokenServesTheHighestPriorityProvider(): void
    {
        $this->registerProvider('pubvana.blog', 'blog', 20, true);
        $this->registerProvider('pubvana.pages', 'page', 30, true);

        $loader = $this->loader();
        $loader->dispatchHomepage();

        self::assertSame(['pubvana.blog'], $this->served);
        self::assertSame(0, $loader->notFound);
    }

    public function testADeclinedProviderHandsOverToTheNextOne(): void
    {
        $this->registerProvider('pubvana.pages', 'page', 10, false);
        $this->registerProvider('pubvana.blog', 'blog', 20, true);

        $loader = $this->loader(['CMS.homepageType' => 'page']);
        $loader->dispatchHomepage();

        // Pages was chosen and declined, so Blog took "/" instead.
        self::assertSame(['pubvana.pages', 'pubvana.blog'], $this->served);
        self::assertSame(0, $loader->notFound);
    }

    public function testAThrowingProviderIsSkippedAndTheNextOneServes(): void
    {
        $this->adext->register('homepage', 'provider', 'pubvana.broken', [
            'label'    => 'Broken',
            'token'    => 'broken',
            'priority' => 10,
            'callable' => static function (): bool {
                throw new \RuntimeException('provider exploded');
            },
        ]);
        $this->registerProvider('pubvana.blog', 'blog', 20, true);

        $loader = $this->loader(['CMS.homepageType' => 'broken']);
        $loader->dispatchHomepage();

        self::assertSame(['pubvana.blog'], $this->served);
        self::assertSame(0, $loader->notFound);
    }

    public function testAProviderWithoutACallableIsSkipped(): void
    {
        // Registration requires a callable, so this covers the hand-built
        // config array: the provider is skipped rather than fatal.
        $this->adext->register('homepage', 'provider', 'pubvana.blog', [
            'label'    => 'Blog Feed',
            'token'    => 'blog',
            'callable' => 'no-such-callable',
        ]);

        $loader = $this->loader();
        $loader->dispatchHomepage();

        self::assertSame([], $this->served);
        self::assertSame(1, $loader->notFound);
    }

    public function testAnUnknownTokenFallsBackToPriorityOrder(): void
    {
        $this->registerProvider('pubvana.blog', 'blog', 20, true);

        // 'pages' is the pre-registry value: the provider that answered to it
        // is gone, so priority order still has to serve a homepage.
        $loader = $this->loader(['CMS.homepageType' => 'pages']);
        $loader->dispatchHomepage();

        self::assertSame(['pubvana.blog'], $this->served);
        self::assertSame(0, $loader->notFound);
    }

    public function testNoProvidersAtAllRendersTheNotFound(): void
    {
        $loader = $this->loader();
        $loader->dispatchHomepage();

        self::assertSame([], $this->served);
        self::assertSame(1, $loader->notFound);
    }

    /**
     * Register a homepage provider whose callable records that it ran.
     *
     * @param bool $handles What the callable returns: true serves "/"
     */
    private function registerProvider(string $key, string $token, int $priority, bool $handles): void
    {
        $test = $this;
        $this->adext->register('homepage', 'provider', $key, [
            'label'    => $token,
            'token'    => $token,
            'priority' => $priority,
            'callable' => function () use ($test, $key, $handles): bool {
                $test->served[] = $key;

                return $handles;
            },
        ]);
    }

    /**
     * Loader over the mapped engine, with the 404 seam recorded.
     *
     * @param array<string, mixed> $settings Values the settings double serves
     */
    private function loader(array $settings = []): RecordingPluginLoader
    {
        $app = $this->app([
            'adext'    => fn(): ExtensionRegistry => $this->adext,
            'settings' => fn(): FakeHomepageSettings => new FakeHomepageSettings($settings),
        ]);
        \Flight::setEngine($app);

        return new RecordingPluginLoader(
            $app,
            $app->router(),
            '/tmp/pubvana-homepage-plugins',
            '/tmp/pubvana-homepage-vendor'
        );
    }
}

/**
 * Loader that records the 404 seam instead of rendering the themed page.
 */
final class RecordingPluginLoader extends PluginLoader
{
    public int $notFound = 0;

    protected function homepageNotFound(): void
    {
        $this->notFound++;
    }
}

/**
 * Settings double serving a fixed map.
 */
final class FakeHomepageSettings
{
    /** @param array<string, mixed> $values */
    public function __construct(private array $values = [])
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }
}
