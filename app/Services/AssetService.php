<?php

declare(strict_types=1);

namespace Pubvana\Services;

use flight\Engine;

/**
 * AssetService - Unified asset serving for themes, plugins, and vendor packages.
 *
 * Resolves asset paths to actual files, validates security, and serves files
 * with proper MIME types and caching headers. Eliminates the need to copy
 * assets to public/ directories.
 *
 * @package Pubvana\Services
 */
class AssetService
{

    /** @var Engine<object>|null The FlightPHP app instance (absent on the early asset path) */
    protected ?Engine $app;

    /** @var string[] Allowed asset types */
    protected array $allowedTypes = ['plugin', 'theme', 'vendor'];

    /** @var string[] Allowed file extensions */
    protected array $allowedExtensions = [
        'css', 'js', 'json',
        'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico',
        'woff', 'woff2', 'ttf', 'eot', 'otf',
    ];

    /** @var array<string, string> MIME type mapping */
    protected array $mimeTypes = [
        'css'   => 'text/css',
        'js'    => 'application/javascript',
        'json'  => 'application/json',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'gif'   => 'image/gif',
        'svg'   => 'image/svg+xml',
        'webp'  => 'image/webp',
        'ico'   => 'image/x-icon',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf',
        'eot'   => 'application/vnd.ms-fontobject',
        'otf'   => 'font/otf',
    ];

    /**
     * The Flight engine is optional: this service resolves and streams files
     * from disk and never touches the framework, so the early asset path
     * (asset-server.php) constructs it without booting the app. When an app
     * is present, 404s go through halt(); without one, a bare 404 is sent.
     *
     * @param Engine<object>|null $app
     */
    public function __construct(?Engine $app = null)
    {
        $this->app = $app;
    }

    /**
     * Send a 404 through the app when one exists, else a bare 404.
     */
    private function fail404(): void
    {
        if ($this->app !== null) {
            $this->app->halt(404, 'Asset not found');
            return;
        }

        http_response_code(404);
        exit;
    }

    /**
     * Resolve an asset path to an absolute file path.
     *
     * @param string $type Asset type (plugin, theme, vendor)
     * @param string $name Plugin/theme name or vendor/package
     * @param string $path Relative path within assets directory
     * @return string|null Absolute file path or null if not found/invalid
     */
    public function resolve(string $type, string $name, string $path): ?string
    {
        // Validate type
        if (!in_array($type, $this->allowedTypes, true)) {
            return null;
        }

        // Validate extension
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions, true)) {
            return null;
        }

        // Reject traversal outright. A ".." path segment, a backslash, or a
        // NUL byte never reaches the filesystem. Stripping these is not
        // enough: "....//" survives a strip as "../".
        if (str_contains($path, '\\') || str_contains($path, "\0")) {
            return null;
        }

        foreach (explode('/', $path) as $segment) {
            if ($segment === '..' || $segment === '.') {
                return null;
            }
        }

        // Build the base directory and the file path. The base is fixed from
        // the type and the basenamed name, so it never moves with the request.
        $root = PROJECT_ROOT;
        $base = null;

        switch ($type) {
            case 'plugin':
                // $name is a single component (plugin id); strip any path
                $base = $root . '/plugins/' . basename($name) . '/assets';
                break;

            case 'theme':
                // $name is a single component (theme id); strip any path
                $base = $root . '/themes/' . basename($name) . '/assets';
                break;

            case 'vendor':
                // Vendor packages: name is "vendor/package"
                $parts = explode('/', $name, 2);
                if (count($parts) !== 2) {
                    return null;
                }
                // Strip any path from each component to prevent traversal
                $base = $root . '/vendor/' . basename($parts[0]) . '/' . basename($parts[1]) . '/assets';
                break;
        }

        if ($base === null) {
            return null;
        }

        $filePath = $base . '/' . $path;

        // Validate file exists and is readable
        if (!is_file($filePath) || !is_readable($filePath)) {
            return null;
        }

        // Security check: the resolved file must sit inside the fixed base.
        // realpath() collapses ".." and symlinks, so a link pointing outside
        // the base fails this test. The trailing separator stops a sibling
        // like "assets-extra" from passing a prefix match.
        $realPath = realpath($filePath);
        $realBase = realpath($base);

        if ($realPath === false || $realBase === false
            || !str_starts_with($realPath, $realBase . DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $realPath;
    }

    /**
     * Get MIME type for a file path.
     */
    public function getMimeType(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return $this->mimeTypes[$extension] ?? 'application/octet-stream';
    }

    /**
     * Serve a file with proper headers.
     *
     * @param string $filePath Absolute file path
     * @return void
     */
    public function serve(string $filePath): void
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            $this->fail404();
            return;
        }

        // Never let a browser MIME-sniff an asset into something executable
        // (SVG is the script-capable type in the allow-list). The full-app
        // path gets this from SecurityHeadersMiddleware; the early asset path
        // in asset-server.php never runs that middleware, so the header is set
        // here to cover both entry points.
        header('X-Content-Type-Options: nosniff');

        // Asset responses are binary streams; Tracy's debug bar cannot and
        // should not inject into them (it throws when Content-Length is set).
        if (class_exists(\Tracy\Debugger::class)) {
            \Tracy\Debugger::$showBar = false;
        }

        $mimeType = $this->getMimeType($filePath);
        $lastModified = filemtime($filePath);
        if ($lastModified === false) {
            $this->fail404();
            return;
        }
        // Quoted, per RFC 7232 entity-tag syntax. Browsers echo the tag back
        // with its quotes, so an unquoted value never matches.
        $etag = '"' . md5($filePath . $lastModified) . '"';
        $cacheHeaders = [
            'Cache-Control' => 'public, max-age=86400', // 1 day
            'ETag'          => $etag,
            'Last-Modified' => gmdate('D, d M Y H:i:s', $lastModified) . ' GMT',
        ];

        // Revalidate. A 304 still carries the validators.
        if ($this->isFresh(
            (string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? ''),
            (string) ($_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? ''),
            $lastModified,
            $etag
        )) {
            header('HTTP/1.1 304 Not Modified');
            foreach ($cacheHeaders as $name => $value) {
                header("{$name}: {$value}");
            }
            exit;
        }

        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filePath));
        foreach ($cacheHeaders as $name => $value) {
            header("{$name}: {$value}");
        }

        readfile($filePath);
        exit;
    }

    /**
     * Whether the client's copy is still fresh, from the two revalidation
     * headers. If-None-Match outranks If-Modified-Since when both arrive
     * (RFC 7232), and an unparsable date is ignored.
     */
    public function isFresh(string $ifNoneMatch, string $ifModifiedSince, int $lastModified, string $etag): bool
    {
        $ifNoneMatch = trim($ifNoneMatch);

        if ($ifNoneMatch !== '') {
            // The header may carry a weak prefix (W/"x"), a comma-separated
            // list, or the bare hash older clients send. Take all three.
            $bare = trim($etag, '"');

            foreach (explode(',', $ifNoneMatch) as $candidate) {
                if (trim(ltrim(trim($candidate), 'W/'), '"') === $bare) {
                    return true;
                }
            }

            return false;
        }

        $since = trim($ifModifiedSince) === '' ? false : strtotime($ifModifiedSince);

        return $since !== false && $since >= $lastModified;
    }
}
