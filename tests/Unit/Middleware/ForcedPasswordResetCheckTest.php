<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Middleware;

use Enlivenapp\FlightShield\Models\User;
use Enlivenapp\FlightShield\Models\UserIdentity;
use flight\Engine;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Middleware\ForcedPasswordResetCheck;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * ForcedPasswordResetCheck against an in-memory database.
 *
 * The flag itself is Shield's, seeded here through UserIdentity the way the
 * Users admin sets it, and read back through the real
 * User::requiresPasswordReset() the check calls.
 *
 * Auth and the request are lightweight stand-ins: the check only needs to
 * know who is signed in and what was asked for.
 */
#[CoversClass(ForcedPasswordResetCheck::class)]
final class ForcedPasswordResetCheckTest extends TestCase
{
    private const SHIELD_CONFIG = [
        'routePrepend' => 'auth',
        'redirects'    => [
            'force_reset' => '/auth/reset-password',
        ],
    ];

    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();

        // Flight's own convention: halt() and jsonHalt() skip exit() when
        // this is set, which is what lets a test read the response.
        putenv('PHPUNIT_TEST=1');
    }

    protected function tearDown(): void
    {
        putenv('PHPUNIT_TEST');
        parent::tearDown();
    }

    // -----------------------------------------------------------------
    // Exempt requests
    // -----------------------------------------------------------------

    public function testExemptsTheResetFlowAssetsAndLogout(): void
    {
        $check = $this->check();

        self::assertTrue($check->isExempt('/auth/reset-password', 'GET'));
        self::assertTrue($check->isExempt('/auth/reset-password', 'POST'));
        self::assertTrue($check->isExempt('/auth/reset-password/process', 'POST'));
        self::assertTrue($check->isExempt('/auth/logout', 'POST'));
        self::assertTrue($check->isExempt('/assets/theme/default/css/pubvana.css', 'GET'));
    }

    public function testDoesNotExemptAnythingElse(): void
    {
        $check = $this->check();

        // Shield's logout is POST only, and the router would 405 a GET.
        self::assertFalse($check->isExempt('/auth/logout', 'GET'));
        self::assertFalse($check->isExempt('/auth/reset-password/process', 'GET'));
        self::assertFalse($check->isExempt('/admin/users', 'GET'));
        self::assertFalse($check->isExempt('/', 'GET'));
        self::assertFalse($check->isExempt('/blog/hello-world', 'GET'));
        self::assertFalse($check->isExempt('/assetsx/theme/default/css/pubvana.css', 'GET'));
    }

    public function testExemptPathsMatchTheWayTheRouterMatchesThem(): void
    {
        $check = $this->check();

        // Trailing slash and mixed case reach the same route.
        self::assertTrue($check->isExempt('/auth/reset-password/', 'GET'));
        self::assertTrue($check->isExempt('/Auth/Reset-Password', 'GET'));
        self::assertTrue($check->isExempt('/AUTH/LOGOUT', 'POST'));
    }

    public function testExemptPathsFollowShieldConfig(): void
    {
        $check = $this->check(['routePrepend' => 'account', 'redirects' => ['force_reset' => '/account/password']]);

        self::assertTrue($check->isExempt('/account/password', 'GET'));
        self::assertTrue($check->isExempt('/account/password/process', 'POST'));
        self::assertTrue($check->isExempt('/account/logout', 'POST'));
        self::assertFalse($check->isExempt('/auth/reset-password', 'GET'));
    }

    // -----------------------------------------------------------------
    // Who gets sent where
    // -----------------------------------------------------------------

    public function testAnonymousRequestPasses(): void
    {
        self::assertNull($this->check()->pendingResetUrl());
    }

    public function testSignedInUserWithoutTheFlagPasses(): void
    {
        $user = $this->seedUser('ada', 'ada@example.com');

        self::assertNull($this->check(user: $user)->pendingResetUrl());
    }

    public function testFlaggedUserIsSentToTheResetPage(): void
    {
        $user = $this->seedUser('ada', 'ada@example.com', true);

        self::assertSame('/auth/reset-password', $this->check(user: $user)->pendingResetUrl());
    }

    public function testFlaggedUserIsSentToTheConfiguredResetPage(): void
    {
        $user = $this->seedUser('ada', 'ada@example.com', true);
        $check = $this->check(
            ['routePrepend' => 'account', 'redirects' => ['force_reset' => '/account/password']],
            $user
        );

        self::assertSame('/account/password', $check->pendingResetUrl());
    }

    public function testFlaggedUserPassesOnAnExemptPath(): void
    {
        $user = $this->seedUser('ada', 'ada@example.com', true);

        self::assertNull($this->check(user: $user, request: new FakeRequest('/auth/reset-password'))->pendingResetUrl());
        self::assertNull($this->check(user: $user, request: new FakeRequest('/auth/logout', 'POST'))->pendingResetUrl());
        self::assertNull($this->check(user: $user, request: new FakeRequest('/assets/theme/default/css/pubvana.css'))->pendingResetUrl());
    }

    public function testQueryStringDoesNotHideAnUnprotectedPath(): void
    {
        $user = $this->seedUser('ada', 'ada@example.com', true);
        $check = $this->check(user: $user, request: new FakeRequest('/admin/users?page=2'));

        self::assertSame('/auth/reset-password', $check->pendingResetUrl());
    }

    // -----------------------------------------------------------------
    // The response
    // -----------------------------------------------------------------

    public function testBrowserRequestIsRedirectedToTheResetPage(): void
    {
        $user = $this->seedUser('ada', 'ada@example.com', true);
        $app = $this->engine($user, new FakeRequest('/admin/users'));

        $this->send(fn() => (new ForcedPasswordResetCheck($app))->enforce());

        self::assertSame(303, $app->response()->status());
        self::assertSame('/auth/reset-password', $app->response()->getHeader('Location'));
    }

    public function testRequestThatWantsJsonIsRefusedInsteadOfRedirected(): void
    {
        $user = $this->seedUser('ada', 'ada@example.com', true);
        $app = $this->engine($user, new FakeRequest('/admin/users', accept: 'application/json'));

        $this->send(fn() => (new ForcedPasswordResetCheck($app))->enforce());

        self::assertSame(403, $app->response()->status());
        self::assertSame('application/json', $app->response()->getHeader('Content-Type'));
        self::assertSame(
            ['error' => 'Password reset required.'],
            json_decode($app->response()->getBody(), true)
        );
    }

    public function testAjaxRequestIsRefusedInsteadOfRedirected(): void
    {
        $user = $this->seedUser('ada', 'ada@example.com', true);
        $app = $this->engine($user, new FakeRequest('/admin/themes/recheck', 'POST', ajax: true));

        $this->send(fn() => (new ForcedPasswordResetCheck($app))->enforce());

        self::assertSame(403, $app->response()->status());
    }

    public function testRequestThatPassesIsNotTouched(): void
    {
        $user = $this->seedUser('ada', 'ada@example.com', true);
        $app = $this->engine($user, new FakeRequest('/auth/reset-password'));

        $this->send(fn() => (new ForcedPasswordResetCheck($app))->enforce());

        self::assertSame(200, $app->response()->status());
        self::assertNull($app->response()->getHeader('Location'));
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    private function check(?array $config = null, ?User $user = null, ?FakeRequest $request = null): ForcedPasswordResetCheck
    {
        $app = $this->engine($user, $request ?? new FakeRequest('/admin/users'));
        $app->set('enlivenapp.flight-shield', $config ?? self::SHIELD_CONFIG);

        return new ForcedPasswordResetCheck($app);
    }

    /**
     * @param array<string, mixed>|null $config Shield config to stand in for
     */
    private function engine(?User $user, FakeRequest $request): Engine
    {
        $app = $this->app([
            'db'      => fn(): PDO => $this->pdo,
            'auth'    => fn(): object => new FakeAuth($user),
            'request' => fn(): object => $request,
        ]);

        // Shield's UserIdentity reads \Flight::db(), the global engine.
        \Flight::setEngine($app);

        return $app;
    }

    /**
     * Run the check with the echoed response body swallowed.
     */
    private function send(callable $work): void
    {
        ob_start();

        try {
            $work();
        } finally {
            ob_end_clean();
        }
    }

    private function seedUser(string $username, string $email, bool $forceReset = false): User
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        $user = new User($this->pdo);
        $user->username = $username;
        $user->active = true;
        $user->created_at = $now;
        $user->updated_at = $now;
        $user->insert();

        (new UserIdentity($this->pdo))->createEmailIdentity($user, [
            'email'         => $email,
            'password_hash' => password_hash('Original-Seed-123', PASSWORD_DEFAULT),
        ]);

        if ($forceReset === true) {
            (new UserIdentity($this->pdo))->forcePasswordReset($user, true);
        }

        return $user;
    }
}

/**
 * Request stand-in carrying only what the check reads, plus the base Flight
 * uses when building a redirect target.
 */
final class FakeRequest
{
    public string $base = '/';

    public function __construct(
        public string $url = '/',
        public string $method = 'GET',
        public string $accept = 'text/html',
        public string $type = 'text/html',
        public bool $ajax = false,
    ) {
    }
}

final class FakeAuth
{
    public function __construct(private readonly ?User $user)
    {
    }

    public function loggedIn(): bool
    {
        return $this->user !== null;
    }

    public function user(): ?User
    {
        return $this->user;
    }
}
