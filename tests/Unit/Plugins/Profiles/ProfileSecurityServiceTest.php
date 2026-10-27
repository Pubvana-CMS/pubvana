<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Profiles;

use Enlivenapp\FlightShield\Authentication\Actions\Email2FA;
use Enlivenapp\FlightShield\Models\User;
use Enlivenapp\FlightShield\Result;
use flight\Engine;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Profiles\Services\ProfileSecurityService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * ProfileSecurityService - credentials, password changes and sessions.
 */
#[CoversClass(ProfileSecurityService::class)]
final class ProfileSecurityServiceTest extends TestCase
{
    private PDO $pdo;

    /** @var list<array<string, mixed>> */
    public array $updates = [];
    public Result $updateResult;
    public ?string $sessionId = 'current-session';
    /** @var class-string|null */
    public ?string $twoFactorAction = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        ProfilesSchema::create($this->pdo);
        $this->pdo->exec(
            'CREATE TABLE sessions (
                id            INTEGER PRIMARY KEY AUTOINCREMENT,
                session_id    TEXT NOT NULL UNIQUE,
                user_id       INTEGER,
                ip_address    TEXT,
                user_agent    TEXT,
                payload       TEXT,
                last_activity INTEGER NOT NULL DEFAULT 0,
                created_at    TEXT
            )'
        );

        $this->updates = [];
        $this->updateResult = (new Result())->setSuccess(true);
        $this->sessionId = 'current-session';
        $this->twoFactorAction = null;
    }

    public function testCredentialsSendOnlyTheEmail(): void
    {
        $result = $this->service()->updateCredentials($this->user(7), [
            'email'    => ' ada@example.com ',
            'username' => 'changed',
        ]);

        self::assertTrue($result->isOK());
        self::assertSame([['email' => 'ada@example.com']], $this->updates);
    }

    public function testCredentialsRejectEmptyPayload(): void
    {
        $result = $this->service()->updateCredentials($this->user(7), []);

        self::assertFalse($result->isOK());
        self::assertSame('Enter a new email address.', $result->reason());
        self::assertSame([], $this->updates);
    }

    public function testCredentialsIgnoreUsernameOnly(): void
    {
        $result = $this->service()->updateCredentials($this->user(7), ['username' => 'changed']);

        self::assertFalse($result->isOK());
        self::assertSame([], $this->updates);
    }

    public function testCredentialsReturnShieldFailure(): void
    {
        $this->updateResult = (new Result())->setSuccess(false)->setReason('Email address is already in use.');

        $result = $this->service()->updateCredentials($this->user(7), ['email' => 'taken@example.com']);

        self::assertFalse($result->isOK());
        self::assertSame('Email address is already in use.', $result->reason());
    }

    public function testPasswordRejectsMissingCurrentPassword(): void
    {
        $result = $this->service()->updatePassword($this->user(7), '', 'new-pass', 'new-pass');

        self::assertFalse($result->isOK());
        self::assertSame('Enter your current password.', $result->reason());
        self::assertSame([], $this->updates);
    }

    public function testPasswordRejectsWrongCurrentPassword(): void
    {
        $result = $this->service(false)->updatePassword($this->user(7), 'wrong', 'new-pass', 'new-pass');

        self::assertFalse($result->isOK());
        self::assertSame('Your current password is incorrect.', $result->reason());
        self::assertSame([], $this->updates);
    }

    public function testPasswordRejectsMismatch(): void
    {
        $result = $this->service()->updatePassword($this->user(7), 'old-pass', 'new-pass', 'other');

        self::assertFalse($result->isOK());
        self::assertSame('The new passwords do not match.', $result->reason());
        self::assertSame([], $this->updates);
    }

    public function testPasswordChangeSignsOutOtherSessions(): void
    {
        $user = $this->user(7);
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1), (9, 'grace', 1)");
        $this->insertSession('current-session', 7, time());
        $this->insertSession('other-1', 7, time());
        $this->insertSession('other-2', 7, time());
        $this->insertSession('someone-else', 9, time());
        $this->pdo->exec(
            "INSERT INTO auth_remember_tokens (selector, hashed_validator, user_id, expires)
             VALUES ('sel', 'hash', 7, '2099-01-01 00:00:00')"
        );

        $result = $this->service()->updatePassword($user, 'old-pass', 'new-pass', 'new-pass');

        self::assertTrue($result->isOK());
        self::assertSame([['password' => 'new-pass']], $this->updates);

        $left = $this->pdo->query('SELECT session_id FROM sessions ORDER BY session_id')
            ->fetchAll(PDO::FETCH_COLUMN);
        self::assertSame(['current-session', 'someone-else'], $left);

        $tokens = $this->pdo->query('SELECT COUNT(*) FROM auth_remember_tokens WHERE user_id = 7')
            ->fetchColumn();
        self::assertSame(0, (int) $tokens);
    }

    public function testPasswordChangeDoesNotTouchSessionsWhenShieldFails(): void
    {
        $this->updateResult = (new Result())->setSuccess(false)->setReason('Password is too short.');
        $this->insertSession('other', 7, time());

        $result = $this->service()->updatePassword($this->user(7), 'old-pass', 'new', 'new');

        self::assertFalse($result->isOK());
        self::assertSame('Password is too short.', $result->reason());
        self::assertCount(1, $this->pdo->query('SELECT id FROM sessions')->fetchAll());
    }

    public function testTwoFactorEnabledFollowsShieldConfig(): void
    {
        self::assertFalse($this->service()->twoFactorEnabled());

        $this->twoFactorAction = Email2FA::class;
        self::assertTrue($this->service()->twoFactorEnabled());
    }

    public function testSessionsHideStaleRowsAndMarkCurrent(): void
    {
        $this->insertSession('current-session', 7, time(), 'Mozilla/5.0 (Windows NT 10.0) Chrome/120', '10.0.0.1');
        $this->insertSession('stale', 7, time() - 90000);
        $this->insertSession('other', 9, time());

        $sessions = $this->service()->sessions($this->user(7));

        self::assertCount(1, $sessions);
        self::assertSame('10.0.0.1', $sessions[0]['ip']);
        self::assertSame('Chrome on Windows', $sessions[0]['device']);
        self::assertTrue($sessions[0]['current']);
    }

    public function testRevokeSessionRefusesCurrentSession(): void
    {
        $this->insertSession('current-session', 7, time());
        $id = (int) $this->pdo->query('SELECT id FROM sessions')->fetchColumn();

        self::assertFalse($this->service()->revokeSession($this->user(7), $id));
        self::assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM sessions')->fetchColumn());
    }

    public function testRevokeSessionDeletesAnotherUsersRowOnlyWhenMatching(): void
    {
        $this->insertSession('someone-else', 9, time());
        $id = (int) $this->pdo->query('SELECT id FROM sessions')->fetchColumn();

        self::assertFalse($this->service()->revokeSession($this->user(7), $id));
        self::assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM sessions')->fetchColumn());
    }

    public function testRevokeSessionDeletesOwnOtherSession(): void
    {
        $this->insertSession('other', 7, time());
        $id = (int) $this->pdo->query('SELECT id FROM sessions')->fetchColumn();

        self::assertTrue($this->service()->revokeSession($this->user(7), $id));
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM sessions')->fetchColumn());
    }

    public function testRevokeOtherSessionsKeepsCurrent(): void
    {
        $this->insertSession('current-session', 7, time());
        $this->insertSession('other-a', 7, time());
        $this->insertSession('other-b', 7, time());

        $count = $this->service()->revokeOtherSessions($this->user(7));

        self::assertSame(2, $count);
        $left = $this->pdo->query('SELECT session_id FROM sessions')->fetchAll(PDO::FETCH_COLUMN);
        self::assertSame(['current-session'], $left);
    }

    public function testDeviceLabelFallsBackToBrowser(): void
    {
        $label = $this->invoke($this->service(), 'deviceLabel', ['Mozilla/5.0 (X11; Linux x86_64) Firefox/120']);

        self::assertSame('Firefox on Linux', $label);
    }

    private function user(int $id): User
    {
        $user = new User($this->pdo);
        $user->id = $id;

        return $user;
    }

    private function insertSession(
        string $sessionId,
        int $userId,
        int $lastActivity,
        string $userAgent = 'Mozilla/5.0',
        string $ip = '127.0.0.1'
    ): void {
        $statement = $this->pdo->prepare(
            'INSERT INTO sessions (session_id, user_id, ip_address, user_agent, last_activity)
             VALUES (?, ?, ?, ?, ?)'
        );
        $statement->execute([$sessionId, $userId, $ip, $userAgent, $lastActivity]);
    }

    private function service(bool $passwordMatches = true): ProfileSecurityService
    {
        $test = $this;

        $app = $this->app([
            'db' => static fn(): PDO => $test->pdo,
            'auth' => static fn(): object => new class ($test) {
                public function __construct(private ProfileSecurityServiceTest $t)
                {
                }

                public function users(): object
                {
                    return new class ($this->t) {
                        public function __construct(private ProfileSecurityServiceTest $t)
                        {
                        }

                        /** @param array<string, mixed> $data */
                        public function updateProfile(User $user, array $data): Result
                        {
                            $this->t->updates[] = $data;

                            return $this->t->updateResult;
                        }
                    };
                }
            },
            'session' => static fn(): object => new class ($test) {
                public function __construct(private ProfileSecurityServiceTest $t)
                {
                }

                public function id(): ?string
                {
                    return $this->t->sessionId;
                }
            },
        ]);

        $app->set('plugins', ['enlivenapp/flight-sessions' => ['maxlifetime' => 7200]]);
        $app->set('enlivenapp.flight-shield', ['actions' => ['login' => $this->twoFactorAction]]);

        return new class ($app, $passwordMatches) extends ProfileSecurityService {
            public function __construct(Engine $app, private bool $passwordMatches)
            {
                parent::__construct($app);
            }

            protected function currentPasswordMatches(User $user, string $password): bool
            {
                return $this->passwordMatches;
            }
        };
    }
}
