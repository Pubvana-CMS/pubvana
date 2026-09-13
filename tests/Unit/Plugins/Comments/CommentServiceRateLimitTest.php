<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Comments;

use flight\Engine;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Services\CaptchaService;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Services\RateLimiter;
use Pubvana\Services\SettingsService;
use Pubvana\Plugins\Comments\Services\CommentService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * CommentService per-IP spam gate against an in-memory database.
 *
 * Covers create(): one comment per window per IP, independence between
 * addresses, the disabled (0) setting, and that a rejected captcha does
 * not consume the slot.
 *
 * @package Pubvana\Tests\Unit\Plugins\Comments
 */
#[CoversClass(CommentService::class)]
final class CommentServiceRateLimitTest extends TestCase
{
    private PDO $pdo;
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        $this->cacheDir = sys_get_temp_dir() . '/pv-comments-rl-' . uniqid('', true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->cacheDir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->cacheDir);
        parent::tearDown();
    }

    public function testSecondCommentFromTheSameIpWithinTheWindowIsRejected(): void
    {
        $service = $this->service('30');

        $service->create($this->payload('10.0.0.1'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Please wait a moment before commenting again.');
        $service->create($this->payload('10.0.0.1'));
    }

    public function testDifferentIpsAreIndependent(): void
    {
        $service = $this->service('30');
        $service->create($this->payload('10.0.0.1'));

        $service->create($this->payload('10.0.0.2'));
        self::assertSame(2, $this->commentCount());
    }

    public function testZeroDisablesTheGate(): void
    {
        $service = $this->service('0');
        $service->create($this->payload('10.0.0.1'));
        $service->create($this->payload('10.0.0.1'));
        $service->create($this->payload('10.0.0.1'));

        self::assertSame(3, $this->commentCount());
    }

    public function testTheSettingSurvivesADbRowOverride(): void
    {
        $app = $this->buildApp();
        $app->settings()->set('Comments.rate_limit_seconds', '0');
        $service = new CommentService($this->pdo, $app);
        $service->setRateLimiter(new RateLimiter($this->cacheDir));

        $service->create($this->payload('10.0.0.1'));
        $service->create($this->payload('10.0.0.1'));

        self::assertSame(2, $this->commentCount());
    }

    public function testARejectedCaptchaDoesNotConsumeTheSlot(): void
    {
        $app = $this->buildApp();
        $app->settings()->set('Captcha.provider', 'hcaptcha');
        $app->settings()->set('Captcha.site_key', 'site-1');
        $app->settings()->set('Captcha.secret_key', 'secret-1');
        $app->settings()->set('Captcha.protected', json_encode(['comments']));
        $app->map('captcha', fn(): CaptchaService => $this->rejectingCaptcha($app));

        $service = new CommentService($this->pdo, $app);
        $service->setRateLimiter(new RateLimiter($this->cacheDir));

        try {
            $service->create($this->payload('10.0.0.9', ['captcha_token' => 'tok']));
            self::fail('the rejecting captcha must refuse the comment');
        } catch (\InvalidArgumentException) {
            // Expected: no slot may be consumed.
        }

        $app->map('captcha', fn(): CaptchaService => $this->acceptingCaptcha($app));
        $comment = $service->create($this->payload('10.0.0.9', ['captcha_token' => 'tok']));
        self::assertSame(1, $this->commentCount());
        self::assertSame('pending', (string) $comment->status);
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function payload(string $ip, array $extra = []): array
    {
        return array_merge([
            'commentable_type' => 'blog',
            'commentable_id'   => 1,
            'body'             => 'Hello world',
            'ip_address'       => $ip,
            'guest_name'       => 'Ada',
        ], $extra);
    }

    /**
     * CommentService over the real settings store with a temp-dir limiter
     * so no test state leaks through the shared cache directory.
     */
    private function service(string $rateLimitSeconds): CommentService
    {
        $app = $this->buildApp();
        $app->settings()->set('Comments.rate_limit_seconds', $rateLimitSeconds);

        $service = new CommentService($this->pdo, $app);
        $service->setRateLimiter(new RateLimiter($this->cacheDir));

        return $service;
    }

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

    private function acceptingCaptcha(Engine $app): CaptchaService
    {
        return new class($app) extends CaptchaService {
            protected function postToSiteverify(string $endpoint, array $payload): ?array
            {
                return ['success' => true];
            }
        };
    }

    private function rejectingCaptcha(Engine $app): CaptchaService
    {
        return new class($app) extends CaptchaService {
            protected function postToSiteverify(string $endpoint, array $payload): ?array
            {
                return ['success' => false];
            }
        };
    }

    private function commentCount(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) AS c FROM comments');

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
    }
}
