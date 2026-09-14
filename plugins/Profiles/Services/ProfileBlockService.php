<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Profiles\Services;

use Enlivenapp\FlightShield\Models\User;
use Pubvana\Plugins\Blog\Models\Post;
use Pubvana\Plugins\Pages\Models\Page;
use Pubvana\Services\UrlService;
use flight\Engine;

/**
 * ProfileBlockService - Data provider for the Author Card block.
 *
 * The block renders a profile card beneath blog posts and pages. The
 * provider inspects the current request URI to decide which content type
 * is being viewed, checks the placement's toggles, resolves the author
 * from the content slug, and returns the profile data for the template.
 *
 * @package Pubvana\Plugins\Profiles
 */
class ProfileBlockService
{
    /** @var Engine<object> Flight application instance */
    protected Engine $app;

    /**
     * @param Engine<object> $app
     */
    public function __construct(Engine $app)
    {
        $this->app = $app;
    }

    /**
     * Build the author card data for the current request.
     *
     * Returns an empty author payload (the template renders nothing) when
     * the current URI is not a blog post or page, the matching toggle is
     * off, or the content has no author or profile.
     *
     * @param array<string, mixed> $options Saved placement options
     * @return array<string, mixed>
     */
    public function provide(array $options): array
    {
        $options = array_merge([
            'title'         => 'About the Author',
            'show_on_blog'  => 1,
            'show_on_pages' => 0,
            'show_avatar'   => 1,
            'show_socials'  => 1,
        ], $options);

        $path = (string) parse_url($this->app->request()->url ?? '/', PHP_URL_PATH);
        $path = rtrim($path, '/');

        $blogPrefix  = $this->app->pluginLoader()->routePrefix('pubvana/blog');
        $pagesPrefix = $this->app->pluginLoader()->routePrefix('pubvana/pages');

        $authorId = null;

        if ($this->pathMatches($path, $blogPrefix)) {
            if (empty($options['show_on_blog'])) {
                return $this->emptyPayload($options);
            }
            $authorId = $this->postAuthorId($this->slugFromPath($path, $blogPrefix));
        } elseif ($this->pathMatches($path, $pagesPrefix)) {
            if (empty($options['show_on_pages'])) {
                return $this->emptyPayload($options);
            }
            $authorId = $this->pageAuthorId($this->slugFromPath($path, $pagesPrefix));
        }

        if ($authorId === null) {
            return $this->emptyPayload($options);
        }

        $username = $this->usernameFor($authorId);

        if ($username === '') {
            return $this->emptyPayload($options);
        }

        $profile = $this->app->profiles()->findByUserId($authorId);
        if ($profile === null) {
            return $this->emptyPayload($options);
        }

        $avatarUrl = '';
        if (!empty($profile->avatar)) {
            $avatarUrl = '/' . ltrim($profile->avatar, '/');
        }

        // Render guard: only a full http(s) URL becomes a navigable href.
        // Legacy rows stored before scheme validation may still hold
        // javascript: or other junk; those render as no link at all.
        $safeWebsite = UrlService::isSafeExternalUrl($profile->website ?? null)
            ? ($profile->website ?? null)
            : null;

        return [
            'author' => [
                'name'          => $profile->display_name !== null ? $profile->display_name : $username,
                'username'      => $username,
                'url'           => '/profile/' . $username,
                'bio'           => $profile->bio,
                'avatar_url'    => $avatarUrl,
                'safe_website'  => $safeWebsite,
                'twitter_url'   => UrlService::normalizeExternalUrl(ltrim((string) $profile->twitter, '@'), 'https://twitter.com/'),
                'facebook_url'  => UrlService::normalizeExternalUrl(ltrim((string) $profile->facebook, '@'), 'https://facebook.com/'),
                'linkedin_url'  => UrlService::normalizeExternalUrl(ltrim((string) $profile->linkedin, '@'), 'https://linkedin.com/in/'),
            ],
            'title'        => (string) ($options['title'] ?? 'About the Author'),
            'show_avatar'  => !empty($options['show_avatar']),
            'show_socials' => !empty($options['show_socials']),
        ];
    }

    /**
     * Payload that renders nothing: no author, but the placement options
     * still flow through so the template can branch on them.
     *
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    protected function emptyPayload(array $options): array
    {
        return [
            'author'       => null,
            'title'        => (string) ($options['title'] ?? 'About the Author'),
            'show_avatar'  => !empty($options['show_avatar']),
            'show_socials' => !empty($options['show_socials']),
        ];
    }

    /**
     * Whether the request path is under the given prefix (or equals it).
     */
    protected function pathMatches(string $path, string $prefix): bool
    {
        $prefix = rtrim($prefix, '/');
        return $path === $prefix || str_starts_with($path, $prefix . '/');
    }

    /**
     * Extract the slug segment following the prefix.
     */
    protected function slugFromPath(string $path, string $prefix): string
    {
        $prefix = rtrim($prefix, '/');
        $slug = substr($path, strlen($prefix) + 1);
        return trim($slug, '/');
    }

    /**
     * Author id for a published post slug, or null when not found.
     */
    protected function postAuthorId(string $slug): ?int
    {
        if ($slug === '') {
            return null;
        }
        return (new Post($this->app->db()))->authorIdForSlug($slug);
    }

    /**
     * Author id for a page slug, or null when not found.
     */
    protected function pageAuthorId(string $slug): ?int
    {
        if ($slug === '') {
            return null;
        }
        return (new Page($this->app->db()))->createdByForSlug($slug);
    }

    /**
     * Username for a user id, or empty string when the user is gone.
     */
    protected function usernameFor(int $userId): string
    {
        $user = (new User($this->app->db()))->findById($userId);
        return $user !== null ? (string) $user->username : '';
    }
}