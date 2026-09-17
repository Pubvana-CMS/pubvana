<?php

declare(strict_types=1);

namespace Pubvana\Plugins\AiAssistant\Controllers;

use Pubvana\Controllers\Api\ApiBaseController;
use Pubvana\Plugins\AiAssistant\Models\AiKey;

/**
 * AiApiController - Base class for the /api/ai/* API controllers.
 *
 * Provides bearer-key auth, deny-all grants, the {status, data, errors}
 * response shape, the audit log, and tolerant access to peer services.
 * The help endpoints are defined here. The per-resource endpoints (posts,
 * pages, comments, redirects, navigation, broken links, analytics, fact
 * checking) are defined in subclasses. Content and SEO logic that more
 * than one resource uses lives on the AI service, not this class.
 *
 * Grants are deny-all. An action the key is not granted fails with a
 * hard HTTP error.
 *
 * @package Pubvana\Plugins\AiAssistant\Controllers
 */
class AiApiController extends ApiBaseController
{
    public function __construct(\flight\Engine $app)
    {
        parent::__construct($app, 'pubvana.ai');
    }

    // -----------------------------------------------------------------
    // Help
    // -----------------------------------------------------------------

    public function help(): void
    {
        $helpPath = $this->apiPrefix('pubvana/ai') . '/help';
        $token = $this->bearerToken();
        if ($token === null) {
            // No key sent: the guide is where new clients start, so the
            // failure hints at how to get set up (steps in errors[0].next).
            $this->app->ai()->log($this->method(), $this->path(), 'error', null, null, null, 'Missing bearer token.');
            $this->app->jsonHalt([
                'status' => 'error',
                'data'   => null,
                'errors' => [[
                    'code'    => 401,
                    'message' => 'Missing Authentication header. Send the key as "Authorization: Bearer <key>".',
                    'next'    => [
                        'Have a site admin create an API key under Tools > AI Assistant.',
                        'Ask the admin to tick the permissions the assistant is allowed to use.',
                        'Then call GET ' . $helpPath . ' again with the key to receive the full guide.',
                    ],
                ]],
            ], 401);
        }

        $key = $this->requireKey();

        $permissions = array_keys($this->app->ai()->helpCatalog());
        $available = [];
        foreach ($permissions as $permission) {
            if ($this->app->ai()->hasGrant($key, $permission)) {
                $available[] = $permission;
            }
        }

        // /api/ai/help doubles as the interactive grant guide, but presumably
        // callers hold more grants than the catalog shows by default. List
        // everything so the caller can request grants precisely.
        $this->log($key, 'ok', null, null, 'Grant guide requested.');
        $this->ok([
            'guide'      => $this->app->ai()->helpGroups(),
            'grants_held' => $available,
            'envelope'   => 'All responses use {status, data, errors}.',
            'auth'       => 'Send the API key as "Authorization: Bearer <key>".',
        ]);
    }

    public function helpPermission(string $permission): void
    {
        $key = $this->requireKey();

        $catalog = $this->app->ai()->helpCatalog();
        if (!isset($catalog[$permission])) {
            $this->log($key, 'error', null, null, "Unknown permission '{$permission}'.");
            $this->fail(404, "Unknown permission '{$permission}'. See GET " . $this->apiPrefix('pubvana/ai') . '/help for the catalog.');
        }

        $entry = $catalog[$permission];
        $entry['granted'] = $this->app->ai()->hasGrant($key, $permission);

        $this->log($key, 'ok', null, null, "Help requested for '{$permission}'.");
        $this->ok($entry);
    }

    // -----------------------------------------------------------------
    // Request and response helpers
    // -----------------------------------------------------------------

    /**
     * Authenticate the request and return the key, or terminate with 401.
     */
    protected function requireKey(): AiKey
    {
        $token = $this->bearerToken();
        if ($token === null) {
            $this->app->ai()->log($this->method(), $this->path(), 'error', null, null, null, 'Missing bearer token.');
            $this->fail(401, 'Missing Authentication header. Send the key as "Authorization: Bearer <key>".');
        }

        $auth = $this->app->ai()->authenticate($token);
        if ($auth['key'] === null) {
            $this->app->ai()->log(
                $this->method(),
                $this->path(),
                'error',
                $auth['key_known'] ?? null,
                null,
                null,
                $auth['error'] ?? 'Authentication failed.'
            );
            $this->fail(401, $auth['error'] ?? 'Authentication failed.');
        }

        return $auth['key'];
    }

    /**
     * Require a specific grant or terminate with a 403 hard failure.
     */
    protected function requireGrant(AiKey $key, string $permission): void
    {
        if (!$this->app->ai()->hasGrant($key, $permission)) {
            $this->app->ai()->log($this->method(), $this->path(), 'denied', $key, null, null, "Missing grant: {$permission}");
            $this->fail(403, "This API key does not have the '{$permission}' grant.");
        }
    }

    protected function log(AiKey $key, string $outcome, ?string $entityType, ?int $entityId, ?string $detail): void
    {
        $this->app->ai()->log($this->method(), $this->path(), $outcome, $key, $entityType, $entityId, $detail);
    }

    /**
     * @return mixed Whatever the named facade returns, or never (503 halt)
     */
    protected function svc(string $name)
    {
        try {
            return $this->app->{$name}();
        } catch (\Throwable $e) {
            $this->app->ai()->log($this->method(), $this->path(), 'error', null, null, null, "Unavailable service: {$name}");
            $this->fail(503, "The '{$name}' feature is not available right now.");
        }
    }

    /**
     * @param array<int|string, mixed> $data
     */
    protected function ok(array $data): void
    {
        $this->app->jsonHalt([
            'status' => 'ok',
            'data'   => $data,
            'errors' => [],
        ], 200);
    }

    protected function fail(int $status, string $message): never
    {
        $this->app->jsonHalt([
            'status' => 'error',
            'data'   => null,
            'errors' => [['code' => $status, 'message' => $message]],
        ], $status);
    }
}