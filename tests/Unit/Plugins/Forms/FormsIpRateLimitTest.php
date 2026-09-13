<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Forms;

use flight\Engine;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Services\CaptchaService;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Services\RateLimiter;
use Pubvana\Services\SettingsService;
use Pubvana\Plugins\Forms\Services\FormsService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * FormsService per-IP spam gate against an in-memory database.
 *
 * Covers submitForm(): the session gate plus the shared per-IP limiter,
 * so a fresh session no longer buys a new slot; independence between
 * addresses; the disabled (0) window; and that a failed validation does
 * not consume the slot.
 *
 * @package Pubvana\Tests\Unit\Plugins\Forms
 */
#[CoversClass(FormsService::class)]
final class FormsIpRateLimitTest extends TestCase
{
    private PDO $pdo;
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        $this->cacheDir = sys_get_temp_dir() . '/pv-forms-rl-' . uniqid('', true);

        // The session gate shares the per-test PHP session; start it once
        // and blank it so one test's rate marks cannot block the next.
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        foreach (glob($this->cacheDir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->cacheDir);
        parent::tearDown();
    }

    public function testSecondSubmissionFromTheSameIpWithinTheWindowIsRejected(): void
    {
        $service = $this->service();
        $form = $this->publishedForm($service);

        self::assertTrue($service->submitForm($form, ['name' => 'Ada'], ['ip_address' => '127.0.0.1'])['ok']);

        $result = $service->submitForm($form, ['name' => 'Ada'], ['ip_address' => '127.0.0.1']);

        self::assertFalse($result['ok']);
        self::assertContains('Please wait a moment before submitting again.', $result['errors']);
        self::assertSame(1, $this->submissionCount());
    }

    public function testDifferentIpsAreIndependent(): void
    {
        $service = $this->service();
        $form = $this->publishedForm($service);

        self::assertTrue($service->submitForm($form, ['name' => 'Ada'], ['ip_address' => '10.0.0.1'])['ok']);
        self::assertTrue($service->submitForm($form, ['name' => 'Bob'], ['ip_address' => '10.0.0.2'])['ok']);

        self::assertSame(2, $this->submissionCount());
    }

    public function testZeroDisablesTheIpGate(): void
    {
        $service = $this->service(0);
        $form = $this->publishedForm($service);

        self::assertTrue($service->submitForm($form, ['name' => 'Ada'], ['ip_address' => '10.0.0.1'])['ok']);
        self::assertTrue($service->submitForm($form, ['name' => 'Ada'], ['ip_address' => '10.0.0.1'])['ok']);

        self::assertSame(2, $this->submissionCount());
    }

    public function testAFailedValidationDoesNotConsumeTheSlot(): void
    {
        $service = $this->service();
        $form = $this->publishedForm($service);

        // Missing required field: rejected without burning the IP slot.
        $result = $service->submitForm($form, [], ['ip_address' => '10.0.0.3']);
        self::assertFalse($result['ok']);

        self::assertTrue($service->submitForm($form, ['name' => 'Ada'], ['ip_address' => '10.0.0.3'])['ok']);
        self::assertSame(1, $this->submissionCount());
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * Fresh engine wired with the real settings store, extension registry,
     * and captcha service over the shared in-memory database.
     */
    private function buildApp(): Engine
    {
        $app = $this->app([
            'db'       => fn(): PDO => $this->pdo,
            'settings' => $this->singleton(fn(): SettingsService => new SettingsService(\Flight::app())),
            'adext'    => $this->singleton(fn(): ExtensionRegistry => new ExtensionRegistry()),
            'captcha'  => $this->singleton(fn(): CaptchaService => new CaptchaService(\Flight::app())),
        ]);
        \Flight::setEngine($app);

        return $app;
    }

    /**
     * Wrap a lazy provider in a per-engine singleton guard: Flight
     * resolves mapped services through repeated calls, so a bare
     * closure would hand back a fresh instance every time.
     *
     * @param callable $provider Zero-argument factory
     * @return callable Singleton-guarded factory
     */
    private function singleton(callable $provider): callable
    {
        return function () use ($provider) {
            static $instance = null;
            if ($instance === null) {
                $instance = $provider();
            }
            return $instance;
        };
    }

    /**
     * FormsService with the session rate limit disabled and a temp-dir
     * per-IP limiter so no test state leaks through the shared cache.
     *
     * @param int $seconds Per-IP window (0 disables)
     */
    private function service(int $seconds = 10): FormsService
    {
        $app = $this->buildApp();
        $service = new FormsService($this->pdo, $app, [
            'route_prefix'       => '/forms',
            'per_page'           => 25,
            'submissions_per_page' => 25,
            'rate_limit_seconds' => $seconds,
        ]);
        $service->setRateLimiter(new RateLimiter($this->cacheDir));

        return $service;
    }

    /**
     * A published form with one required text field.
     */
    private function publishedForm(FormsService $service): object
    {
        return $service->createForm([
            'name'              => 'Contact',
            'slug'              => 'contact',
            'status'            => 'published',
            'submit_label'      => 'Send',
            'success_message'   => 'Thanks',
            'notification_emails' => '',
            'field_definitions' => json_encode([
                [
                    'type'     => 'text',
                    'name'     => 'name',
                    'label'    => 'Name',
                    'required' => true,
                    'width'    => 'full',
                    'options'  => [],
                ],
            ]),
        ]);
    }

    private function submissionCount(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) AS c FROM form_submissions');

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
    }
}
