<?php

declare(strict_types=1);

namespace Pubvana\Controllers\Public;

/**
 * ErrorController - Themed 404 pages.
 *
 * Browser requests that produce a 404, either an unmatched URL or an
 * internal halt(404) call, render the standard site output: full global
 * data, the theme's errors/error.tpl content, wrapped in the active
 * theme's layout. Admin, API, JSON, and AJAX requests keep the
 * framework's plain responses.
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
        if (PHP_SAPI === 'cli' || !$this->wantsThemedHtml()) {
            $this->haltPlain($status, $message, $fromNotFound);
            return;
        }

        $response = $this->app->response();
        if ($response->getHeader('Cache-Control') === null) {
            $response->cache(0);
        }
        $response->clearBody();
        $response->status($status);
        $response->write($this->buildErrorPage($status, $message));
        $response->send();

        exit; // @codeCoverageIgnore
    }

    /**
     * Whether this request can display a themed HTML error page.
     *
     * Mirrors ProductionErrorHandler::wantsJson(): JSON/AJAX traffic is
     * excluded, as are admin and API routes.
     */
    public function wantsThemedHtml(): bool
    {
        $request = $this->app->request();

        if (str_contains($request->accept, 'application/json')
            || str_contains($request->type, 'application/json')
            || $request->ajax
        ) {
            return false;
        }

        $parsed = parse_url($request->url, PHP_URL_PATH);
        $path = is_string($parsed) ? $parsed : '';
        if ($path === '' || str_starts_with($path, '/admin') || str_starts_with($path, '/api')) {
            return false;
        }

        return true;
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
        // _notFound()/_halt() bypass the dispatcher on purpose: we are
        // inside a before('halt') filter and must not re-enter it.
        if ($message === '' && $fromNotFound) {
            $this->app->_notFound();
            return;
        }
        $this->app->_halt($status, $message);
    }
}