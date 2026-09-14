<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Controllers\Admin;

use flight\Engine;
use flight\util\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Controllers\Admin\LoginSecController;
use Pubvana\Controllers\Admin\CaptchaAdminController;
use Pubvana\Controllers\Admin\EmailAdminController;
use Pubvana\Tests\Support\TestCase;

/**
 * Login, captcha and email settings controllers.
 */
#[CoversClass(LoginSecController::class)]
#[CoversClass(CaptchaAdminController::class)]
#[CoversClass(EmailAdminController::class)]
final class LoginCaptchaEmailControllerTest extends TestCase
{
    /** @var array<string, mixed> */
    public array $fetches = [];
    /** @var list<string> */
    public array $redirects = [];
    /** @var array<string, list<string>> */
    public array $flashes = [];
    /** @var array<string, mixed> */
    public array $store = [];
    /** @var array<string, mixed> */
    public array $areasSaved = [];
    /** @var array<string, mixed> */
    public array $mailerSaved = [];
    /** @var array<string, mixed>|null */
    public ?array $mailerTest = null;

    protected function setUp(): void
    {
        parent::setUp();
        \Pubvana\Tests\Support\Sqlite::recreate();
        $this->fetches = [];
        $this->redirects = [];
        $this->flashes = [];
        $this->store = [];
        $this->areasSaved = [];
        $this->mailerSaved = [];
        $this->mailerTest = null;
    }

    public function testLoginIndexRendersFields(): void
    {
        $app = $this->engine(loginTabs: [[
            'fields' => [
                ['key' => 'Shield.allow', 'default' => true],
                ['key' => '', 'default' => false],
            ],
        ]]);
        (new LoginSecController($app))->index();

        self::assertSame('admin/login_sec', $this->fetches[0]['view']);
        self::assertSame('Login', $this->fetches[0]['data']['pageTitle']);
        self::assertCount(2, $this->fetches[0]['data']['fields']);
        self::assertTrue($this->fetches[0]['data']['fields'][0]['value']);
    }

    public function testLoginSaveWritesBooleans(): void
    {
        $app = $this->engine(
            loginTabs: [['fields' => [['key' => 'Shield.a'], ['key' => 'Shield.b'], ['key' => '']]]],
            data: ['settings' => ['Shield.a' => '1']]
        );
        (new LoginSecController($app))->save();

        self::assertTrue($this->store['Shield.a']);
        self::assertFalse($this->store['Shield.b']);
        self::assertSame(['/admin/login-sec'], $this->redirects);
        self::assertStringContainsString('(2 settings)', $this->flashes['login_sec_flash'][0]);
    }

    public function testLoginSaveSingular(): void
    {
        $app = $this->engine(
            loginTabs: [['fields' => [['key' => 'Shield.a']]]],
            data: ['settings' => []]
        );
        (new LoginSecController($app))->save();
        self::assertStringContainsString('(1 setting)', $this->flashes['login_sec_flash'][0]);
    }

    public function testCaptchaIndexMasksPassword(): void
    {
        $app = $this->engine(
            captchaTabs: [[
                'fields' => [
                    ['key' => 'Captcha.secret_key', 'type' => 'password', 'default' => 'x'],
                    ['key' => 'Captcha.provider', 'type' => 'select', 'default' => 'none'],
                ],
            ]],
            captchaAreas: ['login' => ['label' => 'Login', 'description' => 'd']],
            captchaProtected: ['login' => true],
            captchaEnabled: true
        );
        $this->store['Captcha.provider'] = 'hcaptcha';
        (new CaptchaAdminController($app))->index();

        self::assertSame('admin/captcha', $this->fetches[0]['view']);
        self::assertSame('', $this->fetches[0]['data']['fields'][0]['value']);
        self::assertSame('hcaptcha', $this->fetches[0]['data']['fields'][1]['value']);
        self::assertTrue($this->fetches[0]['data']['areas']['login']['enabled']);
        self::assertTrue($this->fetches[0]['data']['enabled']);
    }

    public function testCaptchaSaveProviderWhitelistAndSecret(): void
    {
        $app = $this->engine(
            data: ['settings' => ['Captcha.provider' => 'bogus', 'Captcha.site_key' => ' k ', 'Captcha.secret_key' => 's3cr3t'], 'areas' => ['login' => '1']],
            captchaEnabled: false
        );
        $app->set('plugins', ['enlivenapp/flight-sessions' => ['encryption_key' => 'test-deployment-key-0123456789']]);
        (new CaptchaAdminController($app))->save();

        self::assertSame('none', $this->store['Captcha.provider']);
        self::assertSame('k', $this->store['Captcha.site_key']);
        self::assertStringStartsWith('v2:', (string) $this->store['Captcha.secret_key']);
        self::assertSame(['login' => '1'], $this->areasSaved);
        self::assertSame(['/admin/captcha'], $this->redirects);
        self::assertStringContainsString('Pick a provider', $this->flashes['captcha_flash'][0]);
    }

    public function testCaptchaSaveBlankSecretKeepsStored(): void
    {
        $app = $this->engine(
            data: ['settings' => ['Captcha.provider' => 'hcaptcha', 'Captcha.site_key' => 'k', 'Captcha.secret_key' => '  '], 'areas' => 'nope'],
            captchaEnabled: true
        );
        (new CaptchaAdminController($app))->save();

        self::assertArrayNotHasKey('Captcha.secret_key', $this->store);
        self::assertSame([], $this->areasSaved);
        self::assertStringNotContainsString('Pick a provider', $this->flashes['captcha_flash'][0]);
    }

    public function testEmailIndexRenders(): void
    {
        $app = $this->engine(emailTabs: [[
            'fields' => [
                ['key' => 'Mail.host', 'type' => 'text', 'default' => 'h'],
                ['key' => 'Mail.password', 'type' => 'password'],
            ],
        ]]);
        (new EmailAdminController($app))->index();

        self::assertSame('admin/email', $this->fetches[0]['view']);
        self::assertSame('', $this->fetches[0]['data']['fields'][1]['value']);
        self::assertSame('Leave blank to keep the current password', $this->fetches[0]['data']['fields'][1]['placeholder']);
        self::assertCount(2, $this->fetches[0]['data']['recent']);
        self::assertSame(0, $this->fetches[0]['data']['sentCount']);
    }

    public function testEmailSave(): void
    {
        $app = $this->engine(data: ['settings' => ['Mail.host' => 'h']]);
        (new EmailAdminController($app))->save();

        self::assertSame(['Mail.host' => 'h'], $this->mailerSaved);
        self::assertSame(['/admin/email'], $this->redirects);
        self::assertStringContainsString('Saved 2 settings.', $this->flashes['email_flash'][0]);
    }

    public function testEmailSaveNothing(): void
    {
        $app = $this->engine(data: ['settings' => []], mailerResult: ['saved' => 0, 'rejected' => ['X']]);
        (new EmailAdminController($app))->save();
        self::assertStringContainsString('Nothing to save.', $this->flashes['email_flash'][0]);
        self::assertStringContainsString('Rejected: X', $this->flashes['email_flash'][0]);
    }

    public function testEmailTestRequiresRecipient(): void
    {
        (new EmailAdminController($this->engine(data: ['test_to' => ''])))->test();
        self::assertSame('Enter a recipient address for the test email.', $this->flashes['email_flash'][0]);
        self::assertSame(['/admin/email'], $this->redirects);
    }

    public function testEmailTestSends(): void
    {
        (new EmailAdminController($this->engine(data: ['test_to' => ' a@b.test '])))->test();
        self::assertSame('a@b.test', $this->mailerTest['to']);
        self::assertSame(['/admin/email'], $this->redirects);
    }

    /**
     * @param list<array<string, mixed>> $loginTabs
     * @param list<array<string, mixed>> $captchaTabs
     * @param array<string, mixed> $captchaAreas
     * @param array<string, bool> $captchaProtected
     * @param list<array<string, mixed>> $emailTabs
     * @param array<string, mixed> $mailerResult
     */
    private function engine(
        array $data = [],
        array $loginTabs = [],
        array $captchaTabs = [],
        array $captchaAreas = [],
        array $captchaProtected = [],
        bool $captchaEnabled = false,
        array $emailTabs = [],
        array $mailerResult = ['saved' => 2, 'rejected' => []]
    ): Engine {
        $test = $this;
        $app = $this->app([
            'request' => static fn(): object => new class($data) {
                public Collection $data;
                public Collection $query;
                public function __construct(array $d)
                {
                    $this->data = new Collection($d);
                    $this->query = new Collection([]);
                }
            },
            'settings' => static fn(): object => new class($test) {
                public function __construct(private LoginCaptchaEmailControllerTest $t)
                {
                }
                public function get(string $k, mixed $d = null): mixed
                {
                    return $this->t->store[$k] ?? $d;
                }
                public function set(string $k, mixed $v): void
                {
                    $this->t->store[$k] = $v;
                }
            },
            'adext' => static fn(): object => new class($test, $loginTabs, $captchaTabs, $emailTabs) {
                /** @param list<array<string, mixed>> $login @param list<array<string, mixed>> $captcha @param list<array<string, mixed>> $email */
                public function __construct(private LoginCaptchaEmailControllerTest $t, private array $login, private array $captcha, private array $email)
                {
                }
                /** @return array<string, mixed> */
                public function get(string $type, string $slot, array $c = []): array
                {
                    return match ($slot) {
                        'login_sec' => ['core' => $this->login[0] ?? ['fields' => []]],
                        'captcha' => ['core' => $this->captcha[0] ?? ['fields' => []]],
                        'email' => ['core' => $this->email[0] ?? ['fields' => []]],
                        default => [],
                    };
                }
            },
            'captcha' => static fn(): object => new class($test, $captchaAreas, $captchaProtected, $captchaEnabled) {
                /** @param array<string, mixed> $areas @param array<string, bool> $prot */
                public function __construct(private LoginCaptchaEmailControllerTest $t, private array $areas, private array $prot, private bool $enabled)
                {
                }
                /** @return array<string, mixed> */
                public function areas(): array
                {
                    return $this->areas;
                }
                public function isProtected(string $k): bool
                {
                    return $this->prot[$k] ?? false;
                }
                public function isEnabled(): bool
                {
                    return $this->enabled;
                }
                /** @param array<string, mixed>|string $a */
                public function setProtectedAreas(mixed $a): void
                {
                    $this->t->areasSaved = is_array($a) ? $a : [];
                }
            },
            'mailer' => static fn(): object => new class($test, $mailerResult) {
                /** @param array<string, mixed> $result */
                public function __construct(private LoginCaptchaEmailControllerTest $t, private array $result)
                {
                }
                /** @return list<array<string, string>> */
                public function recent(int $n): array
                {
                    return [['a' => 'b'], ['c' => 'd']];
                }
                /** @param array<string, mixed> $post @return array<string, mixed> */
                public function saveSettings(array $post): array
                {
                    $this->t->mailerSaved = $post;

                    return $this->result;
                }
                /** @return array<string, mixed> */
                public function test(string $to): array
                {
                    $this->t->mailerTest = ['to' => $to];

                    return ['ok' => true];
                }
            },
            'db' => static fn(): \PDO => \Pubvana\Tests\Support\Sqlite::connection(),
            'session' => static fn(): object => new class($test) {
                public function __construct(private LoginCaptchaEmailControllerTest $t)
                {
                }
                public function flash(string $k, mixed $v): void
                {
                    $this->t->flashes[$k][] = $v;
                }
                public function pullFlash(string $k): mixed
                {
                    return 'pulled';
                }
            },
            'view' => static function () use ($test): object {
                return new class($test) {
                    public function __construct(private LoginCaptchaEmailControllerTest $t)
                    {
                    }
                    /** @param array<string, mixed>|null $d */
                    public function fetch(string $v, ?array $d = null): string
                    {
                        $this->t->fetches[] = ['view' => $v, 'data' => $d ?? []];

                        return 'C:' . $v;
                    }
                };
            },
        ]);
        $app->set('admin.topNav', []);
        $app->map('render', function (string $t, array $d) use ($test): void {
            $test->fetches[] = ['view' => 'render:' . $t, 'data' => $d];
        });
        $app->map('redirect', function (string $u) use ($test): void {
            $test->redirects[] = $u;
        });
        \Flight::setEngine($app);

        return $app;
    }
}
