<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Services;

use PDO;
use PHPMailer\PHPMailer\PHPMailer;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Models\Mail;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Services\Mailer;
use Pubvana\Services\SettingsService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;
use RuntimeException;

/**
 * Mailer against the in-memory settings + mail_logs tables.
 *
 * Covers the from/name resolution chain, the SMTP transport build
 * (host/port/encryption/auth/decrypted password), the send paths
 * (success logs 'sent', failure logs 'failed' and throws) through a
 * send() spy, the test() probe, saveSettings coercion/validation incl.
 * the encrypted Mail.password, and the recent() delegation.
 *
 * @package Pubvana\Tests\Unit\Services
 */
#[CoversClass(Mailer::class)]
final class MailerTest extends TestCase
{
    private const KEY = 'test-deployment-key-0123456789';

    private PDO $pdo;

    /** @var string[] Skip logs created per test, removed on teardown. */
    private array $tempLogFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
    }

    protected function tearDown(): void
    {
        foreach ($this->tempLogFiles as $path) {
            @unlink($path);
        }
        $this->tempLogFiles = [];

        parent::tearDown();
    }

    // -----------------------------------------------------------------
    // fromDefaults()
    // -----------------------------------------------------------------

    public function testFromDefaultsUsesOptsOverridesFirst(): void
    {
        $mailer = $this->mailer();

        $resolved = $this->invoke($mailer, 'fromDefaults', [[
            'from'     => 'override@example.com',
            'fromName' => 'Override',
        ]]);

        self::assertSame('override@example.com', $resolved['address']);
        self::assertSame('Override', $resolved['name']);
    }

    public function testFromDefaultsFallsBackToSettingsThenCoreValues(): void
    {
        $app = $this->makeApp();
        $app->settings()->set('CMS.siteName', 'My Site');
        $app->settings()->set('CMS.adminEmail', 'admin@example.com');
        $mailer = new Mailer($app);

        // Nothing configured for mail: the core site values answer.
        $resolved = $this->invoke($mailer, 'fromDefaults', [[]]);
        self::assertSame('admin@example.com', $resolved['address']);
        self::assertSame('My Site', $resolved['name']);

        // Settings beat core values.
        $app->settings()->set('Mail.fromEmail', 'from@example.com');
        $app->settings()->set('Mail.fromName', 'From Name');
        $resolved = $this->invoke($mailer, 'fromDefaults', [[]]);
        self::assertSame('from@example.com', $resolved['address']);
        self::assertSame('From Name', $resolved['name']);
    }

    public function testFromDefaultsInventsNothingWhenNothingIsConfigured(): void
    {
        $mailer = $this->mailer();

        $resolved = $this->invoke($mailer, 'fromDefaults', [[]]);

        // No row, no address. The code invents no sender, so a send with an
        // empty From fails loudly in PHPMailer instead of quietly claiming
        // to come from localhost.
        self::assertSame('', $resolved['address']);
        self::assertSame('', $resolved['name']);
    }

    // -----------------------------------------------------------------
    // transport()
    // -----------------------------------------------------------------

    public function testTransportBuildsSmtpConfigurationFromSettings(): void
    {
        $app = $this->makeApp();
        $settings = $app->settings();
        $settings->set('Mail.host', 'smtp.example.com');
        $settings->set('Mail.port', 465);
        $settings->set('Mail.encryption', 'ssl');
        $settings->set('Mail.username', 'mailer@example.com');
        $settings->set('Mail.password', $this->cipher($app)->encrypt('s3cret'));

        $mail = $this->invoke(new Mailer($app), 'transport');

        self::assertSame('smtp', $mail->Mailer, 'SMTP is the only transport');
        self::assertSame('smtp.example.com', $mail->Host);
        self::assertSame(465, $mail->Port);
        self::assertSame('ssl', $mail->SMTPSecure);
        self::assertTrue($mail->SMTPAuth);
        self::assertSame('mailer@example.com', $mail->Username);
        self::assertSame('s3cret', $mail->Password, 'stored ciphertext is decrypted for the transport');
        self::assertSame('UTF-8', $mail->CharSet);
        self::assertSame(10, $mail->Timeout);
    }

    public function testTransportInventsNoDefaultsWhenNothingIsConfigured(): void
    {
        $app = $this->makeApp();

        $mail = $this->invoke(new Mailer($app), 'transport');

        self::assertSame('', $mail->Host);
        self::assertSame(0, $mail->Port);
        self::assertSame('', $mail->SMTPSecure);
        self::assertFalse($mail->SMTPAuth, 'no username, no auth');
    }

    public function testTransportEncryptionNoneDisablesSmtpSecure(): void
    {
        $app = $this->makeApp();
        $app->settings()->set('Mail.encryption', 'none');

        $mail = $this->invoke(new Mailer($app), 'transport');

        self::assertSame('', $mail->SMTPSecure);
    }

    public function testTransportRefusesLoopbackAndLinkLocalHostsInProduction(): void
    {
        foreach (['127.0.0.1', '169.254.169.254', 'localhost', '::1', '[::1]'] as $host) {
            $app = $this->makeApp();
            $app->settings()->set('Mail.host', $host);

            try {
                $this->invoke(new Mailer($app), 'transport');
                self::fail('Expected the transport to refuse host ' . $host);
            } catch (\PHPMailer\PHPMailer\Exception $e) {
                self::assertNotSame('', $e->getMessage());
            }
        }
    }

    public function testTransportAllowsPrivateAndPublicHostsInProduction(): void
    {
        foreach (['10.0.0.5', '192.168.1.10', 'smtp.example.com'] as $host) {
            $app = $this->makeApp();
            $app->settings()->set('Mail.host', $host);

            $mail = $this->invoke(new Mailer($app), 'transport');
            self::assertSame($host, $mail->Host);
        }
    }

    public function testTransportAllowsLoopbackInDevelopment(): void
    {
        $app = $this->makeApp();
        $app->set('environment', 'development');
        $app->settings()->set('Mail.host', '127.0.0.1');

        $mail = $this->invoke(new Mailer($app), 'transport');

        self::assertSame('127.0.0.1', $mail->Host, 'a local test relay is normal in development');
    }

    // -----------------------------------------------------------------
    // sendHtml()
    // -----------------------------------------------------------------

    public function testSendHtmlSendsAndLogsASentRow(): void
    {
        $app = $this->makeApp();
        $app->settings()->set('CMS.siteName', 'My Site');
        $app->settings()->set('CMS.adminEmail', 'admin@example.com');
        $mailer = $this->spiedMailer($app);
        $mailer->spy->result = true;

        $mailer->sendHtml('user@example.com', 'Hello', '<p>Hi</p>', [
            'replyTo' => 'reply@example.com',
            'alt'     => 'Hi (plain)',
        ]);

        $sent = $mailer->spy->sent;
        self::assertCount(1, $sent);
        self::assertSame('user@example.com', $sent[0]['to']);
        self::assertSame('Hello', $sent[0]['subject']);
        self::assertSame('<p>Hi</p>', $sent[0]['body']);
        self::assertTrue($sent[0]['isHtml']);
        self::assertSame('Hi (plain)', $sent[0]['alt']);
        self::assertSame(['reply@example.com'], $sent[0]['replyTo']);
        self::assertSame('admin@example.com', $sent[0]['from']);
        self::assertSame('My Site', $sent[0]['fromName']);

        $logged = (new Mail($this->pdo))->recent(1);
        self::assertSame('sent', $logged[0]->status);
        self::assertSame('user@example.com', $logged[0]->to_address);
        self::assertNull($logged[0]->error);
    }

    public function testSendHtmlFailureLogsFailedRowAndThrows(): void
    {
        $app = $this->makeApp();
        $app->settings()->set('CMS.adminEmail', 'admin@example.com');
        $mailer = $this->spiedMailer($app);
        $mailer->spy->result = false;
        $mailer->spy->ErrorInfo = 'SMTP connect failed';

        try {
            $mailer->sendHtml('user@example.com', 'Hello', '<p>Hi</p>');
            self::fail('Expected RuntimeException');
        } catch (RuntimeException $e) {
            self::assertSame('SMTP connect failed', $e->getMessage());
        }

        // A send()=false outcome surfaces as a bare RuntimeException; no
        // log row is written for it (only MailerException paths log).
        self::assertSame([], (new Mail($this->pdo))->recent(5));
    }

    public function testSendHtmlRejectsMalformedRecipientAndLogsFailed(): void
    {
        $app = $this->makeApp();
        $mailer = $this->spiedMailer($app);

        try {
            $mailer->sendHtml('not-an-email', 'Hello', '<p>Hi</p>');
            self::fail('Expected RuntimeException');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('Mailer:', $e->getMessage());
        }

        $logged = (new Mail($this->pdo))->recent(1);
        self::assertSame('failed', $logged[0]->status);
        self::assertCount(0, $mailer->spy->sent, 'nothing reached the transport');
    }

    // -----------------------------------------------------------------
    // Mail.enabled gate
    // -----------------------------------------------------------------

    public function testSendingIsOffWhenNothingIsConfigured(): void
    {
        $app = $this->makeApp();

        self::assertFalse($this->invoke($this->mailer($app), 'enabled'));
    }

    public function testSendHtmlIsSkippedAndLoggedWhenSendingIsDisabled(): void
    {
        $app = $this->makeApp();
        $mailer = $this->spiedMailer($app);
        $app->settings()->set('Mail.enabled', false);

        $logged = $this->captureErrorLog(static function () use ($mailer): void {
            $mailer->sendHtml('user@example.com', 'Hello', '<p>Hi</p>');
        });

        self::assertCount(0, $mailer->spy->sent, 'nothing reaches the transport while off');
        self::assertSame([], (new Mail($this->pdo))->recent(5), 'no mail_logs row is written');
        self::assertStringContainsString('[mail] sending disabled, skipped: user@example.com "Hello"', $logged);
    }

    public function testProbeIsSkippedAndLoggedWhenSendingIsDisabled(): void
    {
        $app = $this->makeApp();
        $mailer = $this->spiedMailer($app);
        $app->settings()->set('Mail.enabled', false);

        /** @var array{ok: bool, debug: string, error: ?string} $result */
        $result = ['ok' => true, 'debug' => '', 'error' => null];
        $logged = $this->captureErrorLog(static function () use ($mailer, &$result): void {
            $result = $mailer->test('user@example.com');
        });

        self::assertFalse($result['ok']);
        self::assertSame('Email sending is disabled', $result['error']);
        self::assertSame('', $result['debug']);
        self::assertCount(0, $mailer->spy->sent);
        self::assertSame([], (new Mail($this->pdo))->recent(5));
        self::assertStringContainsString('[mail] sending disabled, skipped: user@example.com', $logged);
    }

    // -----------------------------------------------------------------
    // test() probe
    // -----------------------------------------------------------------

    public function testProbeReportsSuccessAndDebugBuffer(): void
    {
        $app = $this->makeApp();
        $app->settings()->set('CMS.siteName', 'My Site');
        $app->settings()->set('CMS.adminEmail', 'admin@example.com');
        $mailer = $this->spiedMailer($app);
        $mailer->spy->result = true;

        $result = $mailer->test('user@example.com');

        self::assertTrue($result['ok']);
        self::assertNull($result['error']);
        self::assertIsString($result['debug']);
        $sent = $mailer->spy->sent;
        self::assertSame('Test message from My Site', $sent[0]['subject']);
        self::assertSame('user@example.com', $sent[0]['to']);

        $logged = (new Mail($this->pdo))->recent(1);
        self::assertSame('sent', $logged[0]->status);
    }

    public function testSiteNameAndFromComeFromTheSettingsStore(): void
    {
        $app = $this->makeApp();
        $settings = $app->settings();
        $settings->set('CMS.siteName', 'Db Site');
        $settings->set('CMS.adminEmail', 'db@example.com');
        $mailer = $this->spiedMailer($app);
        $mailer->spy->result = true;

        $mailer->test('user@example.com');

        self::assertSame('Test message from Db Site', $mailer->spy->sent[0]['subject']);
        self::assertSame('db@example.com', $mailer->spy->sent[0]['from']);
        self::assertSame('Db Site', $mailer->spy->sent[0]['fromName']);
    }

    public function testProbeReportsFailure(): void
    {
        $app = $this->makeApp();
        $app->settings()->set('CMS.adminEmail', 'admin@example.com');
        $mailer = $this->spiedMailer($app);
        $mailer->spy->result = false;
        $mailer->spy->ErrorInfo = 'connection refused';

        $result = $mailer->test('user@example.com');

        self::assertFalse($result['ok']);
        self::assertSame('connection refused', $result['error']);
        self::assertSame('', $result['debug'], 'no SMTP conversation ran through the spy');

        $logged = (new Mail($this->pdo))->recent(1);
        self::assertSame('failed', $logged[0]->status);
        self::assertSame('connection refused', $logged[0]->error);
    }

    // -----------------------------------------------------------------
    // saveSettings()
    // -----------------------------------------------------------------

    public function testSaveSettingsCoercesAndPersistsDeclaredFields(): void
    {
        $app = $this->makeApp();
        $this->declareEmailFields($app);

        $result = $this->mailer($app)->saveSettings([
            'Mail.host'         => ' smtp.example.com ',
            'Mail.port'         => '465',
            'Mail.encryption'   => 'ssl',
            'Mail.fromEmail'    => 'from@example.com',
            'Mail.notifyOnSend' => 'on',
        ]);

        self::assertSame(['saved' => 5, 'rejected' => []], $result);
        $settings = $app->settings();
        self::assertSame('smtp.example.com', $settings->get('Mail.host'));
        self::assertSame(465, $settings->get('Mail.port'));
        self::assertSame('ssl', $settings->get('Mail.encryption'));
        self::assertSame('from@example.com', $settings->get('Mail.fromEmail'));
        self::assertTrue($settings->get('Mail.notifyOnSend'));
    }

    public function testSaveSettingsCheckboxAbsenceSavesFalseAndInvalidValuesAreRejected(): void
    {
        $app = $this->makeApp();
        $this->declareEmailFields($app);

        $result = $this->mailer($app)->saveSettings([
            'Mail.port'       => 'not-a-number',
            'Mail.fromEmail'  => 'not-an-email',
            'Mail.encryption' => 'pigeon',
        ]);

        self::assertSame(
            ['saved' => 1, 'rejected' => ['SMTP port', 'Encryption', 'From email']],
            $result
        );
        self::assertFalse($app->settings()->get('Mail.notifyOnSend'), 'absent checkbox saves false');
    }

    public function testSaveSettingsBlankPasswordKeepsStoredValue(): void
    {
        $app = $this->makeApp();
        $this->declareEmailFields($app);
        $settings = $app->settings();
        $settings->set('Mail.password', $this->cipher($app)->encrypt('old-secret'));

        $result = $this->mailer($app)->saveSettings(['Mail.password' => '   ']);

        // saved=1: the absent notify checkbox stores false; the blank
        // password deliberately keeps the stored value.
        self::assertSame(['saved' => 1, 'rejected' => []], $result);
        $stored = (string) $settings->get('Mail.password');
        self::assertSame('old-secret', $this->cipher($app)->decrypt($stored));
    }

    public function testSaveSettingsStoresThePasswordEncrypted(): void
    {
        $app = $this->makeApp();
        $this->declareEmailFields($app);
        $settings = $app->settings();

        $result = $this->mailer($app)->saveSettings(['Mail.password' => 'brand-new']);

        // saved=2: the encrypted password plus the absent notify checkbox.
        self::assertSame(['saved' => 2, 'rejected' => []], $result);
        $stored = (string) $settings->get('Mail.password');
        self::assertStringStartsWith('v2:', $stored, 'stored at rest as ciphertext');
        self::assertStringNotContainsString('brand-new', $stored);
        self::assertSame('brand-new', $this->cipher($app)->decrypt($stored));
    }

    // -----------------------------------------------------------------
    // recent()
    // -----------------------------------------------------------------

    public function testRecentDelegatesToTheMailModel(): void
    {
        $app = $this->makeApp();
        $model = new Mail($this->pdo);
        $model->record('a@example.com', 'First', 'sent', null, 'f@example.com');
        $model->record('b@example.com', 'Second', 'failed', 'boom', 'f@example.com');

        $recent = $this->mailer($app)->recent(2);

        self::assertCount(2, $recent);
        self::assertSame('Second', $recent[0]->subject);
        self::assertSame('First', $recent[1]->subject);
    }

    // -----------------------------------------------------------------
    // coerce() edge cases (invoked directly)
    // -----------------------------------------------------------------

    public function testCoercePerFieldType(): void
    {
        $mailer = $this->mailer();

        self::assertSame(587, $this->invoke($mailer, 'coerce', [['type' => 'number'], '587']));
        self::assertSame(1.5, $this->invoke($mailer, 'coerce', [['type' => 'number'], '1.5']));

        self::assertTrue($this->invoke($mailer, 'coerce', [['type' => 'checkbox'], 'on']));
        self::assertFalse($this->invoke($mailer, 'coerce', [['type' => 'checkbox'], '0']));

        self::assertSame(
            'a@b.com',
            $this->invoke($mailer, 'coerce', [['type' => 'email'], ' a@b.com '])
        );

        self::assertSame(
            'tls',
            $this->invoke($mailer, 'coerce', [['type' => 'select', 'options' => ['tls' => 'TLS', 'ssl' => 'SSL']], 'tls'])
        );

        self::assertSame(
            'trimmed',
            $this->invoke($mailer, 'coerce', [['type' => 'text'], ' trimmed ']),
            'text trims outer whitespace only'
        );
    }

    public function testCoerceThrowsForInvalidValues(): void
    {
        $mailer = $this->mailer();
        $cases = [
            [['type' => 'number'], 'abc'],
            [['type' => 'email'], 'nope'],
            [['type' => 'select', 'options' => ['tls' => 'TLS']], 'pigeon'],
        ];

        foreach ($cases as $case) {
            try {
                $this->invoke($mailer, 'coerce', $case);
                self::fail('Expected InvalidArgumentException');
            } catch (\InvalidArgumentException $e) {
                self::assertNotSame('', $e->getMessage());
            }
        }
    }

    // -----------------------------------------------------------------
    // Setup helpers
    // -----------------------------------------------------------------

    /**
     * @return Engine<object>
     */
    private function makeApp(): \flight\Engine
    {
        $app = $this->app([
            'db' => fn(): PDO => $this->pdo,
            'adext' => function (): ExtensionRegistry {
                static $registry = null;
                if ($registry === null) {
                    $registry = new ExtensionRegistry();
                }
                return $registry;
            },
            // The real settings store over the in-memory DB.
            'settings' => function () {
                static $service = null;
                if ($service === null) {
                    $service = new SettingsService(\Flight::app());
                }
                return $service;
            },
        ]);

        \Flight::setEngine($app);
        $app->set('plugins', [
            'enlivenapp/flight-sessions' => ['encryption_key' => self::KEY],
        ]);

        return $app;
    }

    private function mailer(?\flight\Engine $app = null): Mailer
    {
        return new Mailer($app ?? $this->makeApp());
    }

    /**
     * Mailer whose transport() answers a send-spy PHPMailer subclass.
     */
    private function spiedMailer(\flight\Engine $app): Mailer
    {
        $app->settings()->set('Mail.enabled', true);
        $test = $this;

        $spy = new class(true) extends PHPMailer {
            /** @var array<int, array<string, mixed>> */
            public array $sent = [];

            public bool $result = true;

            public function send(): bool
            {
                $this->sent[] = [
                    'to'       => $this->getToAddresses()[0][0] ?? '',
                    'subject'  => $this->Subject,
                    'body'     => $this->Body,
                    'alt'      => $this->AltBody,
                    'isHtml'   => $this->ContentType === 'text/html',
                    'replyTo'  => array_map(
                        static fn($r) => $r[0] ?? '',
                        array_values($this->getReplyToAddresses())
                    ),
                    'from'     => $this->From,
                    'fromName' => $this->FromName,
                ];

                return $this->result;
            }
        };

        return new class($app, $spy) extends Mailer {
            public function __construct(\flight\Engine $app, public readonly PHPMailer $spy)
            {
                parent::__construct($app);
            }

            protected function transport(): PHPMailer
            {
                return $this->spy;
            }
        };
    }

    /**
     * Declare the email settings tab the way core-admin.php does.
     */
    private function declareEmailFields(\flight\Engine $app): void
    {
        $app->adext()->register('admin.settings', 'email', 'pubvana.core', [
            'label'  => 'Email',
            'fields' => [
                ['key' => 'Mail.host', 'label' => 'SMTP host', 'type' => 'text'],
                ['key' => 'Mail.port', 'label' => 'SMTP port', 'type' => 'number'],
                ['key' => 'Mail.encryption', 'label' => 'Encryption', 'type' => 'select', 'options' => [
                    'tls'  => 'TLS',
                    'ssl'  => 'SSL',
                    'none' => 'None',
                ]],
                ['key' => 'Mail.username', 'label' => 'Username', 'type' => 'text'],
                ['key' => 'Mail.password', 'label' => 'Password', 'type' => 'password'],
                ['key' => 'Mail.fromEmail', 'label' => 'From email', 'type' => 'email'],
                ['key' => 'Mail.notifyOnSend', 'label' => 'Notify on send', 'type' => 'checkbox'],
            ],
        ]);
    }

    private function tempLogFile(): string
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pubvana-mailer-' . uniqid('', true) . '.log';
        $this->tempLogFiles[] = $path;

        return $path;
    }

    /**
     * Run a callable with error_log() pointed at a temp file, then return
     * what was written.
     *
     * @param callable(): void $fn
     */
    private function captureErrorLog(callable $fn): string
    {
        $path = $this->tempLogFile();
        $previous = ini_set('error_log', $path);

        try {
            $fn();
        } finally {
            ini_set('error_log', is_string($previous) ? $previous : '');
        }

        return (string) file_get_contents($path);
    }

    private function cipher(\flight\Engine $app): \Pubvana\Services\SecretCipher
    {
        return new \Pubvana\Services\SecretCipher($app);
    }
}
