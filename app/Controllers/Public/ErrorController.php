<?php

declare(strict_types=1);

namespace Pubvana\Controllers\Public;

use Pubvana\Controllers\Admin\AdminController;

/**
 * ErrorController - Themed 404 pages.
 *
 * Browser requests that produce a 404, either an unmatched URL or an
 * internal halt(404) call, render the standard site output: full global
 * data, the theme's errors/error.tpl content, wrapped in the active
 * theme's layout. A signed-in request to an /admin path gets the admin
 * layout and the admin error body instead, and an anonymous request to an
 * admin path is sent to the sign-in page, the same way the admin route
 * gate does it. API, JSON, CLI, and AJAX requests keep the framework's
 * plain responses.
 *
 * Registered from app/config/routes.php as before('notFound') and
 * before('halt') filters, after the plugins load so Redirects' 404
 * logging hooks keep their place in the sequence.
 *
 * @package Pubvana\Controllers\Public
 */
class ErrorController extends PublicController
{
    /**
     * 404 for an unmatched URL (the router's notFound).
     */
    public function showNotFound(): void
    {
        $this->show(404, '', true);
    }

    /**
     * 404 produced by an internal halt() call.
     *
     * @param string $message The halt message (e.g. 'Post not found')
     */
    public function showHalt(string $message): void
    {
        $this->show(404, $message, false);
    }

    /**
     * Render a themed 404, or fall back to a plain framework response
     * when the request cannot carry theme HTML.
     *
     * @param int    $status       HTTP status code (404)
     * @param string $message      Display message for the page body
     * @param bool   $fromNotFound True when the unmatched-URL hook called
     *                             this: the plain fallback preserves
     *                             Flight's default HTML 404 body instead
     *                             of a bare halt message
     */
    public function show(int $status, string $message = '', bool $fromNotFound = false): void
    {
        if (PHP_SAPI === 'cli' || !$this->wantsHtmlPage()) {
            $this->haltPlain($status, $message, $fromNotFound);
            return;
        }

        if ($this->wantsAdminErrorPage()) {
            $this->sendPage($status, $this->buildAdminErrorPage($status, $message));
            return;
        }

        if ($this->wantsAdminLoginRedirect()) {
            $this->redirectToLogin();
            return;
        }

        if (!$this->wantsThemedHtml()) {
            $this->haltPlain($status, $message, $fromNotFound);
            return;
        }

        $this->sendPage($status, $this->buildErrorPage($status, $message));
    }

    /**
     * Put a rendered error page on the response and stop the request.
     *
     * The exit is load bearing. These handlers run as before filters, and
     * once a filter returns the engine runs its own mapped method, which
     * writes the same body again.
     */
    private function sendPage(int $status, string $html): void
    {
        $response = $this->app->response();
        if ($response->getHeader('Cache-Control') === null) {
            $response->cache(0);
        }
        $response->clearBody();
        $response->status($status);
        $response->write($html);
        $response->send();

        exit; // @codeCoverageIgnore
    }

    /**
     * Whether the request can display an HTML page at all. JSON, AJAX, and
     * CLI traffic cannot.
     */
    public function wantsHtmlPage(): bool
    {
        $request = $this->app->request();

        return !(
            str_contains($request->accept, 'application/json')
            || str_contains($request->type, 'application/json')
            || $request->ajax
        );
    }

    /**
     * Whether this request should get the admin error page.
     *
     * Admin paths get the admin shell once a session is established, so the
     * page has a layout, a title, a lang, one h1, and the usual landmarks.
     */
    public function wantsAdminErrorPage(): bool
    {
        return $this->wantsHtmlPage()
            && $this->isAdminPath()
            && $this->signedIn();
    }

    /**
     * Whether this request belongs at the sign-in page.
     *
     * An anonymous request to an /admin path is sent there, the same way
     * PermissionMiddleware treats a gated admin route.
     */
    public function wantsAdminLoginRedirect(): bool
    {
        return $this->wantsHtmlPage()
            && $this->isAdminPath()
            && !$this->signedIn();
    }

    /**
     * Request path, or '' when the URL cannot be parsed.
     */
    private function requestPath(): string
    {
        $parsed = parse_url($this->app->request()->url, PHP_URL_PATH);

        return is_string($parsed) ? $parsed : '';
    }

    /**
     * Whether the request targets the admin area.
     */
    private function isAdminPath(): bool
    {
        return str_starts_with($this->requestPath(), '/admin');
    }

    /**
     * Whether a session identifies a signed-in user.
     */
    private function signedIn(): bool
    {
        try {
            return (bool) $this->app->auth()->loggedIn();
        } catch (\Throwable $e) {
            // No auth service on this engine: nothing is signed in.
            return false;
        }
    }

    /**
     * Send the request to the sign-in page.
     *
     * The target comes from the Shield config, matching
     * PermissionMiddleware::before() so both routes land in the same place.
     */
    private function redirectToLogin(): void
    {
        $config = $this->app->get('enlivenapp.flight-shield') ?? [];
        $target = is_array($config) && isset($config['redirects']['login'])
            ? (string) $config['redirects']['login']
            : '/auth/login';

        $this->app->redirect($target);
        $this->app->halt(303);
    }

    /**
     * Render the admin error page and return the finished HTML.
     *
     * @param int    $status  HTTP status code
     * @param string $message Display message; empty uses the status name
     */
    public function buildAdminErrorPage(int $status, string $message = ''): string
    {
        $display = $message !== '' ? $message : $this->friendlyMessage($status);

        return (new AdminController($this->app))->renderErrorPage($status, $display);
    }

    /**
     * Whether this request can display a themed HTML error page.
     *
     * Mirrors ProductionErrorHandler::wantsJson(): JSON/AJAX traffic is
     * excluded, as are admin and API routes.
     */
    public function wantsThemedHtml(): bool
    {
        if (!$this->wantsHtmlPage()) {
            return false;
        }

        $path = $this->requestPath();

        return !($path === '' || str_starts_with($path, '/admin') || str_starts_with($path, '/api'));
    }

    /**
     * Render the themed error page and return the finished HTML.
     *
     * @param int    $status  HTTP status code
     * @param string $message Display message
     *
     * @return string The rendered layout HTML
     */
    public function buildErrorPage(int $status, string $message = ''): string
    {
        ob_start();
        try {
            $this->renderErrorPage($status, $message);

            return (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }

    /**
     * Plain framework response for non-themed requests, preserving
     * exactly what preceded the themed 404 feature.
     */
    private function haltPlain(int $status, string $message, bool $fromNotFound): void
    {
        if ($fromNotFound) {
            // Let Flight's own notFound run. This is a before filter, so the
            // engine calls the mapped Engine::_notFound() once we return, and
            // that call writes the body. Sending here as well is what printed
            // it twice.
            return;
        }

        $this->app->_halt($status, $message);
    }
}