<?php

declare(strict_types=1);

namespace Pubvana\Middleware;

use flight\Engine;
use Pubvana\Services\PasswordResetService;

/**
 * ForcedPasswordResetCheck - sends a user with a pending password reset to
 * the reset page.
 *
 * Shield stores the flag (auth_identities.force_reset) and knows how to read
 * it (User::requiresPasswordReset(), reached through
 * PasswordResetService::requiresReset()). Shield's own middleware only runs
 * on the routes it is attached to, so this runs from
 * app/config/bootstrap.php before $app->start() and covers every route the
 * app has.
 *
 * Anonymous requests pass through, as do the few requests a flagged user
 * still has to be able to make: the reset page and its POST target, the
 * logout POST, and /assets/* for the CSS on the reset page. A browser gets a
 * redirect; a request that is not a browser page load gets a 403.
 *
 * @package Pubvana\Middleware
 */
class ForcedPasswordResetCheck
{
    /**
     * Asset path prefix. The reset page loads theme CSS through
     * /assets/*, so those requests must not be turned away.
     */
    protected const ASSET_PREFIX = '/assets/';

    /** JSON body sent to requests that are not a browser page load. */
    protected const REFUSAL = ['error' => 'Password reset required.'];

    /** @var Engine<object> Flight application instance */
    protected Engine $app;

    protected PasswordResetService $resets;

    /** @var array<string, mixed> Merged flight-shield config */
    protected array $config;

    /**
     * @param Engine<object> $app
     */
    public function __construct(Engine $app)
    {
        $this->app = $app;
        $this->resets = new PasswordResetService($app);
        $this->config = (array) ($app->get('enlivenapp.flight-shield') ?? []);
    }

    /**
     * Send the response and stop the request when a reset is pending.
     *
     * Leaves the response alone when the request passes.
     */
    public function enforce(): void
    {
        $url = $this->pendingResetUrl();

        if ($url === null) {
            return;
        }

        if ($this->wantsJson() === true) {
            $this->app->jsonHalt(self::REFUSAL, 403);
        } else {
            $this->app->redirect($url);
            // redirect() sends the response but does not stop the request.
            // The PHPUnit exemption is Flight's own convention, it lets tests
            // read the response.
            $this->app->halt(303, '', empty(getenv('PHPUNIT_TEST')));
        }
    }

    /**
     * Where a flagged user must be sent, or null when the request passes.
     *
     * Cheapest test first: exempt paths cost no session read and no query,
     * anonymous requests cost no query either, and only a signed-in user
     * pays for the force_reset lookup.
     */
    public function pendingResetUrl(): ?string
    {
        $request = $this->app->request();

        if ($this->isExempt($this->path($request->url), $request->method) === true) {
            return null;
        }

        $auth = $this->app->auth();

        if ($auth->loggedIn() === false) {
            return null;
        }

        $user = $auth->user();

        if ($user === null || $this->resets->requiresReset($user) === false) {
            return null;
        }

        return $this->resetUrl();
    }

    /**
     * Whether this is a request a flagged user must still be able to make.
     *
     * Paths are read from Shield's config rather than written here, so they
     * follow redirects.force_reset and routePrepend if either changes.
     *
     * @param string $path   Request path
     * @param string $method Request method
     */
    public function isExempt(string $path, string $method): bool
    {
        $normalized = strtolower(rtrim($path, '/')) ?: '/';

        if (str_starts_with($normalized, self::ASSET_PREFIX) === true) {
            return true;
        }

        $methods = $this->exemptRequests()[$normalized] ?? null;

        if ($methods === null) {
            return false;
        }

        return $methods === [] || in_array(strtoupper($method), $methods, true);
    }

    /**
     * Request paths a flagged user may still reach, as path => methods.
     * An empty method list means every method.
     *
     * @return array<string, array<int, string>>
     */
    protected function exemptRequests(): array
    {
        $redirects = (array) ($this->config['redirects'] ?? []);
        $prepend = trim((string) ($this->config['routePrepend'] ?? 'auth'), '/');

        $resetUrl = '/' . ltrim((string) ($redirects['force_reset'] ?? '/auth/reset-password'), '/');
        $logoutUrl = '/' . ($prepend === '' ? '' : $prepend . '/') . 'logout';

        return [
            $resetUrl               => ['GET', 'POST'],
            $resetUrl . '/process'  => ['POST'],
            $logoutUrl              => ['POST'],
        ];
    }

    /**
     * The URL a flagged user is sent to, from Shield's config.
     */
    protected function resetUrl(): string
    {
        $redirects = (array) ($this->config['redirects'] ?? []);

        return (string) ($redirects['force_reset'] ?? '/auth/reset-password');
    }

    /**
     * Request path, query string stripped. The router matches on the same
     * value, so both agree on what was asked for.
     */
    protected function path(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        return is_string($path) ? $path : '/';
    }

    /**
     * Whether this request is one a redirect would not help.
     *
     * Mirrors ProductionErrorHandler::wantsJson() and
     * ErrorController::wantsThemedHtml().
     */
    protected function wantsJson(): bool
    {
        $request = $this->app->request();

        return $request->ajax === true
            || str_contains($request->accept, 'application/json')
            || str_contains($request->type, 'application/json');
    }
}
