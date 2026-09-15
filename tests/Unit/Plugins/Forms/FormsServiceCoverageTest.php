<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Forms;

use flight\Engine;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Forms\Models\Form;
use Pubvana\Plugins\Forms\Services\FormsService;
use Pubvana\Services\CaptchaService;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Services\RateLimiter;
use Pubvana\Services\SettingsService;
use Pubvana\Services\UrlService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * FormsService coverage: CRUD, render, submit, flash, cards, notifications.
 *
 * Captcha is off unless a test enforces it (stubbed, no network).
 * Rate limiting uses a temp directory with window 0 unless stated.
 */
#[CoversClass(FormsService::class)]
final class FormsServiceCoverageTest extends TestCase
{
    private PDO $pdo;
    private string $cacheDir;

    /** @var list<array{to: string, subject: string, body: string}> */
    public array $sentEmails = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        $this->cacheDir = sys_get_temp_dir() . '/pv-forms-cov-' . uniqid('', true);
        $this->sentEmails = [];
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

    /** @return Engine<object> */
    private function buildApp(): Engine
    {
        $test = $this;
        $app = $this->app([
            'db' => fn (): PDO => $this->pdo,
            'settings' => $this->singleton(fn (): SettingsService => new SettingsService(\Flight::app())),
            'adext' => $this->singleton(fn (): ExtensionRegistry => new ExtensionRegistry()),
            'captcha' => $this->singleton(fn (): CaptchaService => new CaptchaService(\Flight::app())),
            'url' => $this->singleton(fn (): UrlService => new UrlService(\Flight::app())),
            'mailer' => static fn (): object => new class ($test) {
                public function __construct(private FormsServiceCoverageTest $test)
                {
                }

                public function sendHtml(string $to, string $subject, string $bodyHtml): void
                {
                    $this->test->sentEmails[] = ['to' => $to, 'subject' => $subject, 'body' => $bodyHtml];
                }
            },
            'request' => static fn (): object => new class {
                public function getVar(string $key): ?string
                {
                    return $key === 'REQUEST_URI' ? '/contact' : null;
                }
            },
        ]);
        \Flight::setEngine($app);
        $app->set('flight.base_url', 'http://localhost');

        return $app;
    }

    private function singleton(callable $provider): callable
    {
        return static function () use ($provider): mixed {
            static $instance = null;
            if ($instance === null) {
                $instance = $provider();
            }

            return $instance;
        };
    }

    /**
     * @param array<string, mixed> $config
     */
    /**
     * @param Engine<object> $app
     * @param array<string, mixed> $config
     */
    private function service(Engine $app, array $config = []): FormsService
    {
        $service = new FormsService($this->pdo, $app, array_merge([
            'route_prefix' => '/forms',
            'per_page' => 25,
            'submissions_per_page' => 25,
            'rate_limit_seconds' => 0,
        ], $config));
        $service->setRateLimiter(new RateLimiter($this->cacheDir));

        return $service;
    }

    /**
     * @param array<int, array<string, mixed>> $fields
     * @param array<string, mixed> $extra
     */
    private function makeForm(FormsService $service, array $fields, array $extra = []): Form
    {
        return $service->createForm(array_merge([
            'name' => 'Contact',
            'slug' => 'contact-' . uniqid(),
            'status' => 'published',
            'submit_label' => 'Send',
            'success_message' => 'Thanks!',
            'description' => '',
            'notification_emails' => '',
            'field_definitions' => json_encode($fields),
        ], $extra));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function textField(string $name = 'name', bool $required = true): array
    {
        return [[
            'type' => 'text',
            'name' => $name,
            'label' => ucfirst($name),
            'required' => $required,
            'width' => 'full',
            'options' => [],
        ]];
    }

    private function submissionCount(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) AS c FROM form_submissions');
        self::assertNotFalse($stmt);

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
    }

    /** @return array<string, mixed> */
    private function lastSubmission(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM form_submissions ORDER BY id DESC LIMIT 1');
        self::assertNotFalse($stmt);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        self::assertIsArray($row);

        return $row;
    }

    public function testFormCrudAndFieldSync(): void
    {
        $app = $this->buildApp();
        $service = $this->service($app);

        $form = $this->makeForm($service, $this->textField());
        $id = (int) $form->id;

        self::assertNotNull($service->findForm($id));
        self::assertNull($service->findForm(99999));
        self::assertTrue($service->slugExists((string) $form->slug));
        self::assertFalse($service->slugExists((string) $form->slug, $id));
        self::assertNotNull($service->findPublishedFormBySlug((string) $form->slug));

        $defs = $service->getFieldDefinitions($id);
        self::assertCount(1, $defs);
        self::assertSame('name', $defs[0]['name']);
        self::assertTrue($defs[0]['required']);

        // Update replaces the field set in definition order.
        $updated = $service->updateForm($id, [
            'name' => 'Renamed',
            'field_definitions' => json_encode([
                ['type' => 'email', 'name' => 'email', 'label' => 'Email', 'required' => false, 'width' => 'full', 'options' => []],
                ['type' => 'text', 'name' => 'city', 'label' => 'City', 'required' => false, 'width' => 'half', 'options' => []],
                ['type' => '', 'name' => 'skipped'],
                ['name' => 'also-skipped'],
            ]),
        ]);
        self::assertNotNull($updated);
        self::assertSame('Renamed', (string) $updated->name);
        $defs = $service->getFieldDefinitions($id);
        self::assertSame(['email', 'city'], array_column($defs, 'name'));

        self::assertNull($service->updateForm(99999, ['name' => 'x']));
        self::assertFalse($service->deleteForm(99999));
        self::assertTrue($service->deleteForm($id));
        self::assertNull($service->findForm($id));
        // Soft delete hides the form; field rows remain.
        self::assertCount(2, $service->getFieldDefinitions($id));
    }

    public function testListFormsAndSubmissionsPaging(): void
    {
        $app = $this->buildApp();
        $service = $this->service($app);

        $a = $this->makeForm($service, $this->textField(), ['slug' => 'a-form', 'name' => 'A']);
        $this->makeForm($service, $this->textField(), ['slug' => 'b-form', 'name' => 'B']);

        $list = $service->listForms(1, 1);
        self::assertSame(2, $list['total']);
        self::assertSame(1, $list['page']);
        self::assertSame(1, $list['per_page']);
        self::assertCount(1, $list['items']);
        self::assertCount(2, $service->listAllForms());

        $service->submitForm($a, ['name' => 'Ada'], ['ip_address' => '10.0.0.1']);
        $service->submitForm($a, ['name' => 'Bob'], ['ip_address' => '10.0.0.2']);

        $subs = $service->listSubmissions(1, (int) $a->id, 1);
        self::assertSame(2, $subs['total']);
        self::assertCount(1, $subs['items']);

        $all = $service->listSubmissions(1, null, 25);
        self::assertSame(2, $all['total']);

        $found = $service->findSubmission((int) $this->lastSubmission()['id']);
        self::assertNotNull($found);
        self::assertNull($service->findSubmission(99999));
    }

    public function testDecodeSubmissionPayload(): void
    {
        $app = $this->buildApp();
        $service = $this->service($app);

        self::assertSame(['a' => 1], $service->decodeSubmissionPayload('{"a":1}'));
        self::assertSame([], $service->decodeSubmissionPayload(null));
        self::assertSame([], $service->decodeSubmissionPayload(''));
        self::assertSame([], $service->decodeSubmissionPayload('not-json'));
    }

    public function testDashboardCards(): void
    {
        $app = $this->buildApp();
        $service = $this->service($app);

        $a = $this->makeForm($service, $this->textField(), ['slug' => 'a-form']);
        $this->makeForm($service, $this->textField(), ['slug' => 'b-form', 'status' => 'draft']);
        $service->submitForm($a, ['name' => 'Ada'], ['ip_address' => '10.0.0.1']);

        $cards = $service->dashboardCards();
        self::assertCount(3, $cards);
        self::assertSame(2, $cards[0]['value']);
        self::assertSame(1, $cards[1]['value']);
        self::assertSame(1, $cards[2]['value']);
    }

    public function testDefaultFieldDefinitions(): void
    {
        $app = $this->buildApp();
        $service = $this->service($app);

        $defs = $service->defaultFieldDefinitions();
        self::assertSame(['name', 'email', 'message'], array_column($defs, 'name'));
    }

    public function testRenderPublicForm(): void
    {
        $app = $this->buildApp();
        $service = $this->service($app);

        $form = $this->makeForm($service, [
            ['type' => 'text', 'name' => 'name', 'label' => 'Name', 'required' => true, 'width' => 'full', 'options' => [], 'placeholder' => 'Your name', 'help_text' => 'Help'],
            ['type' => 'select', 'name' => 'topic', 'label' => 'Topic', 'required' => false, 'width' => 'full', 'options' => ['a', 'b']],
            ['type' => 'checkbox', 'name' => 'likes', 'label' => 'Likes', 'required' => false, 'width' => 'full', 'options' => ['x', 'y']],
        ], ['description' => 'Say <hi>']);

        $html = $service->renderPublicForm($form, ['name' => '<b>Ada</b>'], ['e' => 'Boom <b>']);
        self::assertStringContainsString('/forms/submit/' . (int) $form->id, $html);
        self::assertStringContainsString('name="website"', $html);
        self::assertStringContainsString('Name *', $html);
        self::assertStringContainsString('&lt;b&gt;Ada&lt;/b&gt;', $html);
        self::assertStringContainsString('Boom &lt;b&gt;', $html);
        self::assertStringContainsString('Say &lt;hi&gt;', $html);
        self::assertStringContainsString('<select', $html);
        self::assertStringContainsString('name="likes[]"', $html);
        self::assertStringContainsString('Your name', $html);
        self::assertStringContainsString('Help', $html);
    }

    public function testRenderPublicFormShowsFlash(): void
    {
        $app = $this->buildApp();
        $service = $this->service($app);
        $form = $this->makeForm($service, $this->textField());

        $service->storeSubmissionFlash((int) $form->id, ['ok' => true, 'errors' => [], 'values' => []]);
        self::assertStringContainsString('Thanks!', $service->renderPublicForm($form));

        $service->storeSubmissionFlash((int) $form->id, ['ok' => false, 'errors' => ['Bad'], 'values' => ['name' => 'Ada']]);
        $html = $service->renderPublicForm($form);
        self::assertStringContainsString('Bad', $html);
        self::assertStringContainsString('Ada', $html);
    }

    public function testRenderTagBlockAndEmbeds(): void
    {
        $app = $this->buildApp();
        $service = $this->service($app);

        $live = $this->makeForm($service, $this->textField(), ['slug' => 'live-form']);
        $draft = $this->makeForm($service, $this->textField(), ['slug' => 'draft-form', 'status' => 'draft']);
        $liveId = (int) $live->id;

        self::assertStringContainsString('<form', $service->renderTag(['id', (string) $liveId]));
        self::assertStringContainsString('<form', $service->renderTag(['slug', 'live-form']));
        self::assertStringContainsString('<form', $service->renderTag([(string) $liveId]));
        self::assertStringContainsString('<form', $service->renderTag(['live-form']));
        self::assertSame('', $service->renderTag(['id', (string) $draft->id]));
        self::assertSame('', $service->renderTag(['slug', 'draft-form']));
        self::assertSame('', $service->renderTag(['bogus']));
        self::assertSame('', $service->renderTag(['id', '99999']));

        self::assertStringContainsString('<form', $service->renderBlock($liveId, null));
        self::assertStringContainsString('<form', $service->renderBlock(null, 'live-form'));
        self::assertSame('', $service->renderBlock((int) $draft->id, null));
        self::assertSame('', $service->renderBlock(null, 'draft-form'));
        self::assertSame('', $service->renderBlock(null, null));
        self::assertSame('', $service->renderBlock('abc', null));

        self::assertStringContainsString('<form', $service->renderContentEmbeds("Hi {{ forms: id {$liveId} }} bye"));
        self::assertStringContainsString('<form', $service->renderContentEmbeds('Hi {{ forms: slug "live-form" }} bye'));
        self::assertStringContainsString('<form', $service->renderContentEmbeds("Hi {% forms slug 'live-form' %} bye"));
        self::assertStringContainsString('<form', $service->renderContentEmbeds('Hi {% forms id ' . $liveId . ' %} bye'));
        self::assertSame('no form here', $service->renderContentEmbeds('no form here'));
        self::assertSame('', $service->renderContentEmbeds("{{ forms: bogus !!! }}"));
    }

    public function testSubmitHoneypotReportsSuccessWithoutStoring(): void
    {
        $app = $this->buildApp();
        $service = $this->service($app);
        $form = $this->makeForm($service, $this->textField());

        $result = $service->submitForm($form, ['name' => 'Ada', 'website' => 'bot'], ['ip_address' => '10.0.0.1']);

        self::assertTrue($result['ok']);
        self::assertSame(0, $this->submissionCount());
    }

    public function testSubmitValidation(): void
    {
        $app = $this->buildApp();
        $service = $this->service($app);
        $form = $this->makeForm($service, [
            ['type' => 'text', 'name' => 'name', 'label' => 'Name', 'required' => true, 'width' => 'full', 'options' => []],
            ['type' => 'email', 'name' => 'email', 'label' => 'Email', 'required' => false, 'width' => 'full', 'options' => []],
            ['type' => 'select', 'name' => 'topic', 'label' => 'Topic', 'required' => false, 'width' => 'full', 'options' => ['a', 'b']],
            ['type' => 'radio', 'name' => 'pick', 'label' => 'Pick', 'required' => false, 'width' => 'full', 'options' => ['x', 'y']],
            ['type' => 'checkbox', 'name' => 'likes', 'label' => 'Likes', 'required' => false, 'width' => 'full', 'options' => ['m', 'n']],
        ]);

        $missing = $service->submitForm($form, [], ['ip_address' => '10.0.0.1']);
        self::assertFalse($missing['ok']);
        self::assertContains('Name is required.', $missing['errors']);

        $badEmail = $service->submitForm($form, ['name' => 'Ada', 'email' => 'nope'], ['ip_address' => '10.0.0.2']);
        self::assertFalse($badEmail['ok']);
        self::assertContains('Email must be a valid email address.', $badEmail['errors']);

        $badSelect = $service->submitForm($form, ['name' => 'Ada', 'topic' => 'zzz'], ['ip_address' => '10.0.0.3']);
        self::assertFalse($badSelect['ok']);
        self::assertContains('Topic contains an invalid selection.', $badSelect['errors']);

        $badRadio = $service->submitForm($form, ['name' => 'Ada', 'pick' => 'zzz'], ['ip_address' => '10.0.0.4']);
        self::assertFalse($badRadio['ok']);

        $badCheck = $service->submitForm($form, ['name' => 'Ada', 'likes' => ['m', 'zzz']], ['ip_address' => '10.0.0.5']);
        self::assertFalse($badCheck['ok']);
        self::assertContains('Likes contains an invalid selection.', $badCheck['errors']);

        self::assertSame(0, $this->submissionCount());
    }

    public function testSubmitStoresSanitizedPayloadAndMeta(): void
    {
        $app = $this->buildApp();
        $service = $this->service($app);
        $form = $this->makeForm($service, [
            ['type' => 'text', 'name' => 'name', 'label' => 'Name', 'required' => true, 'width' => 'full', 'options' => []],
            ['type' => 'email', 'name' => 'email', 'label' => 'Email', 'required' => false, 'width' => 'full', 'options' => []],
            ['type' => 'textarea', 'name' => 'msg', 'label' => 'Msg', 'required' => false, 'width' => 'full', 'options' => []],
        ]);

        $result = $service->submitForm($form, [
            'name' => '  <b>Ada</b>  ',
            'email' => ' ada@example.com ',
            'msg' => '<p>Hi</p><script>evil()</script>',
            '_csrf_token' => 'tok',
            '_return_url' => '/thanks',
        ], ['ip_address' => '10.0.0.9', 'user_agent' => 'UA', 'referrer' => 'http://localhost/from']);

        self::assertTrue($result['ok']);
        self::assertSame([], $result['values']);

        $row = $this->lastSubmission();
        self::assertSame('10.0.0.9', $row['ip_address']);
        self::assertSame('UA', $row['user_agent']);
        self::assertSame('http://localhost/from', $row['referrer_url']);

        $payload = $service->decodeSubmissionPayload($row['payload_json']);
        self::assertSame('Ada', $payload['name']);
        self::assertSame('ada@example.com', $payload['email']);
        self::assertStringNotContainsString('<script>', (string) $payload['msg']);
        self::assertArrayNotHasKey('_csrf_token', $payload);
        self::assertArrayNotHasKey('_return_url', $payload);
    }

    public function testNormalizeReturnUrl(): void
    {
        $app = $this->buildApp();
        $service = $this->service($app);

        self::assertSame('/thanks', $service->normalizeReturnUrl('/thanks'));
        self::assertSame('/', $service->normalizeReturnUrl('https://evil.example/x'));
        self::assertSame('/', $service->normalizeReturnUrl(null, null));
    }

    public function testFlashRoundTrip(): void
    {
        $app = $this->buildApp();
        $service = $this->service($app);
        $form = $this->makeForm($service, $this->textField());
        $id = (int) $form->id;

        self::assertSame([], $service->consumeSubmissionFlash($id));

        $service->storeSubmissionFlash($id, ['ok' => false, 'errors' => ['Bad'], 'values' => ['name' => 'Ada']]);
        self::assertSame(['ok' => false, 'errors' => ['Bad'], 'values' => ['name' => 'Ada']], $service->consumeSubmissionFlash($id));
        self::assertSame([], $service->consumeSubmissionFlash($id));
    }

    public function testNotificationsSentAndFailuresSwallowed(): void
    {
        $app = $this->buildApp();
        $service = $this->service($app);

        $form = $this->makeForm($service, $this->textField(), ['notification_emails' => 'a@test.example, b@test.example']);
        $result = $service->submitForm($form, ['name' => 'Ada'], ['ip_address' => '10.0.0.11']);
        self::assertTrue($result['ok']);
        self::assertCount(2, $this->sentEmails);
        self::assertSame('a@test.example', $this->sentEmails[0]['to']);

        // Throwing mailer: submission still stores.
        $app->map('mailer', static fn (): object => new class {
            public function sendHtml(string $to, string $subject, string $bodyHtml): void
            {
                throw new \RuntimeException('smtp down');
            }
        });
        $result = $service->submitForm($form, ['name' => 'Bob'], ['ip_address' => '10.0.0.12']);
        self::assertTrue($result['ok']);
        self::assertSame(2, $this->submissionCount());
    }
}
