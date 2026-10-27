<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Profiles\Services;

use Enlivenapp\FlightShield\Authentication\Actions\Email2FA;
use Enlivenapp\FlightShield\Models\RememberToken;
use Enlivenapp\FlightShield\Models\User;
use Enlivenapp\FlightShield\Models\UserIdentity;
use Enlivenapp\FlightShield\Result;
use Enlivenapp\FlightShield\Services\CredentialVerifier;
use flight\Engine;
use Pubvana\Models\Session;

/**
 * ProfileSecurityService - the Shield-backed Security tab.
 *
 * Holds the account changes a user makes to their own login: email,
 * email, password, and the sessions signed in as them. Password writes go
 * through Shield's UserManagement, so validation and hashing follow the
 * configured Shield settings, then the force-reset flag and remember-me
 * tokens are cleared the same way a password reset clears them.
 *
 * Two-factor status is read-only. Shield switches 2FA for the whole site in
 * app/config/shield.php, so there is no per-user flag to flip here.
 *
 * @package Pubvana\Plugins\Profiles
 */
class ProfileSecurityService
{
    /** Default idle timeout, matching enlivenapp/flight-sessions. */
    public const DEFAULT_SESSION_LIFETIME = 7200;

    /** @var Engine<object> */
    protected Engine $app;

    /**
     * @param Engine<object> $app
     */
    public function __construct(Engine $app)
    {
        $this->app = $app;
    }

    /**
     * Change the account email address.
     *
     * Shield refuses duplicates and invalid addresses, and returns its own
     * reason so the caller can flash it unchanged. The username is never
     * editable here: it is the account's stable identity.
     *
     * @param array<string, mixed> $data Posted form data
     */
    public function updateCredentials(User $user, array $data): Result
    {
        $email = trim((string) ($data['email'] ?? ''));

        if ($email === '') {
            return $this->fail('Enter a new email address.');
        }

        return $this->app->auth()->users()->updateProfile($user, ['email' => $email]);
    }

    /**
     * Change the password, after proving the current one.
     *
     * A successful change kills every other session and every remember-me
     * token, so a stolen device cannot ride out the change.
     */
    public function updatePassword(User $user, string $current, string $new, string $confirm): Result
    {
        if ($current === '') {
            return $this->fail('Enter your current password.');
        }

        if (!$this->currentPasswordMatches($user, $current)) {
            return $this->fail('Your current password is incorrect.');
        }

        if ($new === '' || $new !== $confirm) {
            return $this->fail('The new passwords do not match.');
        }

        $result = $this->app->auth()->users()->updateProfile($user, ['password' => $new]);
        if (!$result->isOK()) {
            return $result;
        }

        // Same cleanup a password reset performs: no forced-reset redirect
        // left behind, and no remember-me token survives the change.
        (new UserIdentity($this->app->db()))->forcePasswordReset($user, false);
        (new RememberToken($this->app->db()))->deleteByUser((int) $user->id);

        $this->sessionsModel()->deleteForUser((int) $user->id, $this->currentSessionId());

        return $result;
    }

    /**
     * Whether Shield runs email 2FA after a successful login.
     */
    public function twoFactorEnabled(): bool
    {
        $config = (array) ($this->app->get('enlivenapp.flight-shield') ?? []);
        $action = $config['actions']['login'] ?? null;

        return is_string($action) && $action !== '' && is_a($action, Email2FA::class, true);
    }

    /**
     * The user's live sessions, newest first.
     *
     * Rows older than the idle timeout are hidden: the session package
     * deletes them on its own schedule, so they would otherwise show as
     * devices that are already signed out.
     *
     * @return list<array{id: int, ip: string, device: string, last_active: string, current: bool}>
     */
    public function sessions(User $user): array
    {
        $currentSessionId = $this->currentSessionId();
        $cutoff = time() - $this->sessionLifetime();
        $rows = $this->sessionsModel()->allForUser((int) $user->id, $cutoff);

        $sessions = [];

        foreach ($rows as $row) {
            $sessions[] = [
                'id'          => (int) $row->id,
                'ip'          => (string) ($row->ip_address ?? ''),
                'device'      => $this->deviceLabel($row->user_agent),
                'last_active' => date('M j, Y, g:i a', (int) $row->last_activity),
                'current'     => $currentSessionId !== null && $row->session_id === $currentSessionId,
            ];
        }

        return $sessions;
    }

    /**
     * Sign one device out. The current session is refused: the user has
     * the Logout action for that, and killing it here would only confuse.
     */
    public function revokeSession(User $user, int $id): bool
    {
        $row = $this->sessionsModel()->findForUser((int) $user->id, $id);

        if ($row === null || $row->session_id === $this->currentSessionId()) {
            return false;
        }

        $row->delete();

        return true;
    }

    /**
     * Sign every other device out.
     *
     * @return int Number of sessions removed
     */
    public function revokeOtherSessions(User $user): int
    {
        return $this->sessionsModel()->deleteForUser((int) $user->id, $this->currentSessionId());
    }

    /**
     * The session id behind this request, or null outside a request.
     */
    public function currentSessionId(): ?string
    {
        $id = $this->app->session()->id();

        return is_string($id) && $id !== '' ? $id : null;
    }

    /**
     * Check the supplied password against the user's email identity.
     *
     * Uses Shield's verifier directly rather than Auth::check(), so a
     * mistyped current password is not recorded as a failed login attempt.
     */
    protected function currentPasswordMatches(User $user, string $password): bool
    {
        $config = (array) ($this->app->get('enlivenapp.flight-shield') ?? []);

        return (new CredentialVerifier($this->app, $config))->passwordMatches($user, $password);
    }

    /**
     * A short "Browser on OS" label for a user agent string.
     */
    protected function deviceLabel(?string $userAgent): string
    {
        $agent = (string) $userAgent;

        if ($agent === '') {
            return 'Unknown device';
        }

        $browser = match (true) {
            str_contains($agent, 'Edg')                        => 'Edge',
            str_contains($agent, 'OPR'), str_contains($agent, 'Opera') => 'Opera',
            str_contains($agent, 'Firefox')                    => 'Firefox',
            str_contains($agent, 'Chrome')                     => 'Chrome',
            str_contains($agent, 'Safari')                     => 'Safari',
            default                                            => 'Browser',
        };

        $platform = match (true) {
            str_contains($agent, 'Windows')                    => 'Windows',
            str_contains($agent, 'Android')                    => 'Android',
            str_contains($agent, 'iPhone'), str_contains($agent, 'iPad') => 'iOS',
            str_contains($agent, 'Mac OS X')                   => 'macOS',
            str_contains($agent, 'Linux')                      => 'Linux',
            default                                            => '',
        };

        return $platform === '' ? $browser : $browser . ' on ' . $platform;
    }

    /**
     * Idle timeout in seconds, read from the session package config.
     */
    protected function sessionLifetime(): int
    {
        $plugins = $this->app->get('plugins');
        $config = is_array($plugins) ? ($plugins['enlivenapp/flight-sessions'] ?? []) : [];
        $value = is_array($config) ? ($config['maxlifetime'] ?? null) : null;

        return is_int($value) && $value > 0 ? $value : self::DEFAULT_SESSION_LIFETIME;
    }

    protected function sessionsModel(): Session
    {
        return new Session($this->app->db());
    }

    protected function fail(string $reason): Result
    {
        return (new Result())->setSuccess(false)->setReason($reason);
    }
}
