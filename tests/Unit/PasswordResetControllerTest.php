<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit;

use flight\Engine;
use flight\util\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Controllers\Public\PasswordResetController;
use Pubvana\Services\PasswordResetService;
use Pubvana\Services\PluginView;
use Pubvana\Tests\Support\TestCase;
use stdClass;

/**
 * PasswordResetController: the forgot/reset glue and guard behavior.
 *
 * The controller news up the real PasswordResetService, so tests swap
 * the protected property for a behavior-scripted fake through an
 * anonymous subclass. Auth, request, view, themes and redirects are
 * lightweight stand-ins; redirects are recorded via a mapped provider.
 *
 * Log model:
 *   pw_fetch_log   - every view()->fetch() call (partial + data)
 *   pw_render_log  - every app->render() call (template + data)
 *   pw_redirects   - every app->redirect() call
 *
 * @package Pubvana\Tests\Unit
 */
#[CoversClass(PasswordResetController::class)]
final class PasswordResetControllerTest extends TestCase
{
    /** @var Engine<object>|null Memoized per-test global engine */
    private ?Engine $testApp = null;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['pw_fetch_log'] = [];
        $GLOBALS['pw_render_log'] = [];
        $GLOBALS['pw_redirects'] = [];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['pw_fetch_log'], $GLOBALS['pw_render_log'], $GLOBALS['pw_redirects']);
        parent::tearDown();
    }

    // -----------------------------------------------------------------
    // forgotForm()
    // -----------------------------------------------------------------

    public function testForgotFormRedirectsLoggedInUsersByRole(): void
    {
        $this->controller($this->engine(user: $this->user(true)))->forgotForm();
        self::assertSame(['/admin'], $GLOBALS['pw_redirects'], 'admins land after_login_admin');
        self::assertCount(0, $GLOBALS['pw_fetch_log']);

        $GLOBALS['pw_redirects'] = [];
        $this->controller($this->engine(user: $this->user(false)))->forgotForm();
        self::assertSame(['/members'], $GLOBALS['pw_redirects']);
    }

    public function testForgotFormRendersTheForgotPageForAnonymousVisitors(): void
    {
        $this->controller($this->engine(user: null))->forgotForm();

        $fetch = $GLOBALS['pw_fetch_log'][0];
        self::assertSame('enlivenapp/flight-shield/auth/forgot', $fetch['partial']);
        self::assertNull($fetch['data']['error']);

        $render = $GLOBALS['pw_render_log'][0];
        self::assertSame('enlivenapp/flight-shield/auth/layout', $render['template']);
        self::assertSame('PARTIAL', $render['data']['content']);
        self::assertSame('Forgot Password', $render['data']['authTitle']);
        self::assertSame('We will email you a reset link', $render['data']['authSubtitle']);
    }

    // -----------------------------------------------------------------
    // sendResetLink()
    // -----------------------------------------------------------------

    public function testSendResetLinkRejectsInvalidEmails(): void
    {
        foreach (['   ', 'not-an-email'] as $email) {
            $GLOBALS['pw_fetch_log'] = [];
            $behaviors = [];
            $app = $this->engine(user: null, data: ['email' => $email]);
            $controller = $this->controller($app);
            $controller->swapResets($this->fakeResets($behaviors));

            $controller->sendResetLink();

            $fetch = $GLOBALS['pw_fetch_log'][0];
            self::assertSame('enlivenapp/flight-shield/auth/forgot', $fetch['partial']);
            self::assertSame('Enter a valid email address.', $fetch['data']['error']);
            self::assertTrue($behaviors['failureRecorded'], 'the attempt is rate-limit charged');
        }
    }

    public function testSendResetLinkAnswersIdenticallyWhetherOrNotTheAccountExists(): void
    {
        // Account exists, token issued.
        $behaviors = [];
        $app = $this->engine(user: null, data: ['email' => 'user@example.com']);
        $controller = $this->controller($app);
        $controller->swapResets($this->fakeResets($behaviors));
        $controller->sendResetLink();

        $existsFetch = $GLOBALS['pw_fetch_log'][0];
        $existsRender = $GLOBALS['pw_render_log'][0];

        // No account: the service still answers without complaint.
        $GLOBALS['pw_render_log'] = [];
        $behaviors2 = [];
        $app2 = $this->engine(user: null, data: ['email' => 'nobody@example.com']);
        $controller2 = $this->controller($app2);
        $controller2->swapResets($this->fakeResets($behaviors2));
        $controller2->sendResetLink();

        $missingFetch = $GLOBALS['pw_fetch_log'][0];
        $missingRender = $GLOBALS['pw_render_log'][0];

        // Identical shape either way: same partial, same title, no error.
        self::assertSame($existsFetch['partial'], $missingFetch['partial']);
        self::assertSame($existsRender['data']['authTitle'], $missingRender['data']['authTitle']);
        self::assertArrayNotHasKey('error', $missingFetch['data']);
        self::assertSame(
            'enlivenapp/flight-shield/auth/message',
            $existsFetch['partial'],
            'the success path uses the message page, never an account-exists signal'
        );
        self::assertTrue($behaviors['failureRecorded']);
        self::assertTrue($behaviors2['failureRecorded']);
    }

    public function testSendResetLinkReportsAMailFailureGracefully(): void
    {
        $behaviors = ['issue' => 'throw'];
        $app = $this->engine(user: null, data: ['email' => 'user@example.com']);
        $controller = $this->controller($app);
        $controller->swapResets($this->fakeResets($behaviors));

        $controller->sendResetLink();

        $fetch = $GLOBALS['pw_fetch_log'][0];
        self::assertSame('enlivenapp/flight-shield/auth/message', $fetch['partial']);
        self::assertTrue($fetch['data']['error'] ?? false);

        $render = $GLOBALS['pw_render_log'][0];
        self::assertSame('Email problem', $render['data']['authTitle']);
        self::assertStringContainsString('could not be sent', (string) $fetch['data']['text']);
    }

    // -----------------------------------------------------------------
    // resetForm()
    // -----------------------------------------------------------------

    public function testResetFormRendersTheSessionModeFormForForcedResets(): void
    {
        $behaviors = ['requires' => true];
        $app = $this->engine(user: $this->user(false));
        $controller = $this->controller($app);
        $controller->swapResets($this->fakeResets($behaviors));

        $controller->resetForm();

        $fetch = $GLOBALS['pw_fetch_log'][0];
        self::assertSame('enlivenapp/flight-shield/auth/reset', $fetch['partial']);
        self::assertTrue($fetch['data']['sessionMode']);
        self::assertSame('', $fetch['data']['token']);
        self::assertNull($fetch['data']['error']);
    }

    public function testResetFormWithoutTokenRedirects(): void
    {
        $behaviors = ['requires' => false];
        $app = $this->engine(user: null);
        $controller = $this->controller($app);
        $controller->swapResets($this->fakeResets($behaviors));
        $controller->resetForm();
        self::assertSame(['/auth/forgot'], $GLOBALS['pw_redirects']);

        // Logged in without a pending reset: to their landing url.
        $GLOBALS['pw_redirects'] = [];
        $behaviors2 = ['requires' => false];
        $app2 = $this->engine(user: $this->user(true));
        $controller2 = $this->controller($app2);
        $controller2->swapResets($this->fakeResets($behaviors2));
        $controller2->resetForm();
        self::assertSame(['/admin'], $GLOBALS['pw_redirects']);
    }

    public function testResetFormWithToken(): void
    {
        // Dead token: the expired-link message with a forgot link.
        $behaviors = ['tokenUser' => null];
        $app = $this->engine(user: null, query: ['token' => 'dead']);
        $controller = $this->controller($app);
        $controller->swapResets($this->fakeResets($behaviors));

        $controller->resetForm();

        $fetch = $GLOBALS['pw_fetch_log'][0];
        self::assertSame('enlivenapp/flight-shield/auth/message', $fetch['partial']);
        self::assertTrue($fetch['data']['error']);
        self::assertTrue($fetch['data']['forgotUrl']);
        self::assertSame('Link expired', $GLOBALS['pw_render_log'][0]['data']['authTitle']);

        // Live token: the reset form in token mode.
        $GLOBALS['pw_fetch_log'] = [];
        $GLOBALS['pw_render_log'] = [];
        $behaviors2 = ['tokenUser' => new stdClass()];
        $app2 = $this->engine(user: null, query: ['token' => 'live']);
        $controller2 = $this->controller($app2);
        $controller2->swapResets($this->fakeResets($behaviors2));

        $controller2->resetForm();

        $fetch = $GLOBALS['pw_fetch_log'][0];
        self::assertSame('enlivenapp/flight-shield/auth/reset', $fetch['partial']);
        self::assertFalse($fetch['data']['sessionMode']);
        self::assertSame('live', $fetch['data']['token']);
    }

    // -----------------------------------------------------------------
    // processReset()
    // -----------------------------------------------------------------

    public function testProcessResetSessionMode(): void
    {
        // Failure re-renders the session-mode form with the reason.
        $app = $this->engine(user: $this->user(false), data: ['password' => 'x']);
        $controller = $this->controller($app);
        $behaviors = ['requires' => true, 'resetFor' => 'fail'];
        $controller->swapResets($this->fakeResets($behaviors));

        $controller->processReset();

        $fetch = $GLOBALS['pw_fetch_log'][0];
        self::assertSame('enlivenapp/flight-shield/auth/reset', $fetch['partial']);
        self::assertTrue($fetch['data']['sessionMode']);
        self::assertSame('too short', $fetch['data']['error']);
        self::assertCount(0, $GLOBALS['pw_redirects']);

        // Success redirects to the landing url.
        $GLOBALS['pw_fetch_log'] = [];
        $GLOBALS['pw_redirects'] = [];
        $behaviors2 = ['requires' => true, 'resetFor' => 'ok'];
        $app2 = $this->engine(user: $this->user(false), data: ['password' => 'x']);
        $controller2 = $this->controller($app2);
        $controller2->swapResets($this->fakeResets($behaviors2));

        $controller2->processReset();

        self::assertSame(['/members'], $GLOBALS['pw_redirects']);
        self::assertCount(0, $GLOBALS['pw_fetch_log']);
    }

    public function testProcessResetTokenMode(): void
    {
        // No token at all: to the forgot page.
        $this->controller($this->engine(user: null, data: []))->processReset();
        self::assertSame(['/auth/forgot'], $GLOBALS['pw_redirects']);

        // Failure re-renders the token-mode form with the reason.
        $GLOBALS['pw_redirects'] = [];
        $behaviors = ['resetByToken' => 'fail'];
        $app = $this->engine(user: null, data: ['token' => 'live', 'password' => 'x']);
        $controller = $this->controller($app);
        $controller->swapResets($this->fakeResets($behaviors));

        $controller->processReset();

        $fetch = $GLOBALS['pw_fetch_log'][0];
        self::assertSame('enlivenapp/flight-shield/auth/reset', $fetch['partial']);
        self::assertFalse($fetch['data']['sessionMode']);
        self::assertSame('live', $fetch['data']['token']);
        self::assertSame('too short', $fetch['data']['error']);
        self::assertTrue($behaviors['failureRecorded'], 'failed attempts are rate-limit charged');

        // Success renders the confirmation page.
        $GLOBALS['pw_fetch_log'] = [];
        $GLOBALS['pw_render_log'] = [];
        $behaviors2 = ['resetByToken' => 'ok'];
        $app2 = $this->engine(user: null, data: ['token' => 'live', 'password' => 'x']);
        $controller2 = $this->controller($app2);
        $controller2->swapResets($this->fakeResets($behaviors2));

        $controller2->processReset();

        $fetch = $GLOBALS['pw_fetch_log'][0];
        self::assertSame('enlivenapp/flight-shield/auth/message', $fetch['partial']);
        self::assertSame('Password updated', $GLOBALS['pw_render_log'][0]['data']['authTitle']);
        self::assertStringContainsString('Sign in with your new password', $fetch['data']['text']);
    }

    // -----------------------------------------------------------------
    // afterLoginUrl(), syncThemePath(), siteName()
    // -----------------------------------------------------------------

    public function testAfterLoginUrlRedirectsByRoleAndDefaults(): void
    {
        $controller = $this->controller($this->engine(user: $this->user(true)));
        self::assertSame('/admin', $controller->callAfterLoginUrl());

        $controller2 = $this->controller($this->engine(user: $this->user(false)));
        self::assertSame('/members', $controller2->callAfterLoginUrl());

        // No shield config at all: hardcoded defaults.
        $controller3 = $this->controller($this->engine(user: $this->user(false), shieldConfig: null));
        self::assertSame('/', $controller3->callAfterLoginUrl());
    }

    public function testSyncThemePathFollowsTheActiveTheme(): void
    {
        $app = $this->engine(activeThemeFolder: '_fxauth');
        $view = $app->view();
        self::assertInstanceOf(PluginView::class, $view);
        $view->setThemePath('/stale/Views');

        $this->controller($app)->callSyncThemePath();

        self::assertSame(PROJECT_ROOT . '/themes/_fxauth/Views', $view->getThemePath());

        // No active theme: the fallback path stays.
        $app2 = $this->engine(activeThemeFolder: null);
        $view2 = $app2->view();
        self::assertInstanceOf(PluginView::class, $view2);
        $view2->setThemePath('/fallback/Views');
        $this->controller($app2)->callSyncThemePath();
        self::assertSame('/fallback/Views', $view2->getThemePath());

        // Throwing themes service: swallowed.
        $app3 = $this->engine(themesThrow: true);
        $view3 = $app3->view();
        self::assertInstanceOf(PluginView::class, $view3);
        $view3->setThemePath('/fallback/Views');
        $this->controller($app3)->callSyncThemePath();
        self::assertSame('/fallback/Views', $view3->getThemePath());
    }

    public function testSiteNameFallsBackToPubvana(): void
    {
        self::assertSame('Pubvana', $this->controller($this->engine())->callSiteName());
        self::assertSame('My Site', $this->controller($this->engine(['CMS.siteName' => 'My Site']))->callSiteName());
    }

    // -----------------------------------------------------------------
    // Fixture helpers
    // -----------------------------------------------------------------

    /**
     * Fresh Engine mapped the way the controller expects its world.
     *
     * @param array<string, string>     $data Posted values
     * @param array<string, string>     $query Query values
     * @param array<string, mixed>|null $shieldConfig Value at 'enlivenapp.flight-shield'
     */
    private function engine(
        array $appValues = [],
        ?object $user = null,
        array $data = [],
        array $query = [],
        ?array $shieldConfig = ['redirects' => ['after_login' => '/members', 'after_login_admin' => '/admin']],
        ?string $activeThemeFolder = '_fxauth',
        bool $themesThrow = false
    ): \flight\Engine {
        $app = $this->app([
            'view' => function (): \Pubvana\Services\PluginView {
                static $view = null;
                if ($view === null) {
                    $view = new class extends \Pubvana\Services\PluginView {
                        public function fetch(string $file, ?array $data = null): string
                        {
                            $GLOBALS['pw_fetch_log'][] = ['partial' => $file, 'data' => $data ?? []];

                            return 'PARTIAL';
                        }
                    };
                }
                return $view;
            },
            'auth' => fn(): object => new class($user) {
                public function __construct(private ?object $user) {}
                public function loggedIn(): bool
                {
                    return $this->user !== null;
                }
                public function user(): ?object
                {
                    return $this->user;
                }
            },
            'request' => fn(): object => new class($data, $query) {
                public Collection $data;
                public Collection $query;

                public function __construct(array $data, array $query)
                {
                    $this->data = new Collection($data);
                    $this->query = new Collection($query);
                }
            },
            'themes' => fn(): object => $themesThrow
                ? new class {
                    public function getActive(): never
                    {
                        throw new \RuntimeException('themes table missing');
                    }
                }
                : new class($activeThemeFolder) {
                    public function __construct(private ?string $folder) {}
                    public function getActive(): ?object
                    {
                        if ($this->folder === null) {
                            return null;
                        }
                        $theme = new stdClass();
                        $theme->id = 1;
                        $theme->folder = $this->folder;

                        return $theme;
                    }
                },
        ]);

        if ($shieldConfig !== null) {
            $app->set('enlivenapp.flight-shield', $shieldConfig);
        }
        $app->set('flight.base_url', '/');
        foreach ($appValues as $key => $value) {
            $app->set($key, $value);
        }

        \Flight::setEngine($app);

        return $app;
    }

    /**
     * Anonymous concrete controller with exposed internals plus redirect/
     * render capture wired to the engine.
     */
    private function controller(\flight\Engine $app): object
    {
        $app->map('render', function (string $template, array $data): void {
            $GLOBALS['pw_render_log'][] = ['template' => $template, 'data' => $data];
        });
        $app->map('redirect', function (string $url): void {
            $GLOBALS['pw_redirects'][] = $url;
        });

        return new class($app) extends PasswordResetController {
            public function swapResets(object $fake): void
            {
                $reflection = new \ReflectionProperty(PasswordResetController::class, 'resets');
                $reflection->setAccessible(true);
                $reflection->setValue($this, $fake);
            }

            public function callAfterLoginUrl(): string
            {
                return $this->afterLoginUrl();
            }

            public function callSyncThemePath(): void
            {
                $this->syncThemePath();
            }

            public function callSiteName(): string
            {
                return $this->siteName();
            }
        };
    }

    /**
     * Behavior-scripted PasswordResetService replacement.
     *
     * @param array<string, mixed> $behaviors
     */
    private function fakeResets(array &$behaviors): PasswordResetService
    {
        // The controller property is typed, so the fake must be a
        // PasswordResetService subclass. Signatures stay compatible with the
        // parent (widened object params, real Result/User return types);
        // the parent constructor only reads config the overrides never use.
        return new class($behaviors, new \flight\Engine()) extends PasswordResetService {
            private array $b;

            /** @param array<string, mixed> $b */
            public function __construct(array &$b, \flight\Engine $engine)
            {
                $this->b = &$b;
                parent::__construct($engine);
            }

            public function recordFailure(string $identifier): void
            {
                $this->b['failureRecorded'] = true;
            }

            public function issueResetToken(string $email): bool
            {
                if (($this->b['issue'] ?? '') === 'throw') {
                    throw new \RuntimeException('mailer down');
                }
                return true;
            }

            public function requiresReset(object $user): bool
            {
                return (bool) ($this->b['requires'] ?? false);
            }

            public function findUserByToken(string $token): ?\Enlivenapp\FlightShield\Models\User
            {
                // A real (unhydrated) User model satisfies the parent type;
                // the controller only passes it along.
                return isset($this->b['tokenUser']) ? new \Enlivenapp\FlightShield\Models\User(null) : null;
            }

            public function resetForUser(object $user, string $password, string $confirm): \Enlivenapp\FlightShield\Result
            {
                return $this->result(($this->b['resetFor'] ?? 'ok') === 'ok');
            }

            public function resetByToken(string $token, string $password, string $confirm): \Enlivenapp\FlightShield\Result
            {
                return $this->result(($this->b['resetByToken'] ?? 'ok') === 'ok');
            }

            private function result(bool $ok): \Enlivenapp\FlightShield\Result
            {
                $result = new \Enlivenapp\FlightShield\Result();
                $result->setSuccess($ok)->setReason($ok ? null : 'too short');

                return $result;
            }
        };
    }

    /**
     * A user stand-in exposing only what the controller reads.
     */
    private function user(bool $canAdmin): object
    {
        return new class($canAdmin) {
            public function __construct(private bool $canAdmin) {}
            public function can(string $permission): bool
            {
                return $this->canAdmin && $permission === 'admin.access';
            }
        };
    }
}
