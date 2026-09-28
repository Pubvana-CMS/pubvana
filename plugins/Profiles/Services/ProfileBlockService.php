<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Profiles\Services;

use Enlivenapp\FlightShield\Models\User;
use Pubvana\Plugins\Blog\Models\Post;
use Pubvana\Plugins\Pages\Models\Page;
use Pubvana\Plugins\Profiles\Models\Profile;
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

        $profilesPrefix = $this->app->pluginLoader()->routePrefix('pubvana/profiles');

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
                'url'           => $profilesPrefix . '/' . $authorId,
                'bio'           => $profile->bio,
                'avatar_url'    => $avatarUrl,
                'safe_website'  => $safeWebsite,
                'twitter_url'   => $this->safeUrl($profile->twitter ?? null),
                'facebook_url'  => $this->safeUrl($profile->facebook ?? null),
                'linkedin_url'  => $this->safeUrl($profile->linkedin ?? null),
            ],
            'title'        => (string) ($options['title'] ?? 'About the Author'),
            'show_avatar'  => !empty($options['show_avatar']),
            'show_socials' => !empty($options['show_socials']),
        ];
    }

    /**
     * Profile name and public URL for each given user id, keyed by user id.
     *
     * The name is the profile's display_name. Where that is not populated
     * the Shield username stands in, asked for those ids only, in one query.
     * The URL is /{prefix}/{user id}: the id is the address, so a username is
     * never needed to link to a profile and is never published in one. Ids
     * with no name to show get no entry rather than a null one.
     *
     * @param array<int, int> $userIds
     * @return array<int, array{name: string, url: string|null}>
     */
    public function nameAndUrlFor(array $userIds): array
    {
        $userIds = array_values(array_unique(array_filter(
            array_map('intval', $userIds),
            static fn (int $id): bool => $id > 0
        )));

        if ($userIds === []) {
            return [];
        }

        /** @var array<int, Profile> $profiles */
        $profiles = (new Profile($this->app->db()))->in('user_id', $userIds)->findAll();

        $names = [];
        foreach ($profiles as $profile) {
            $displayName = trim((string) ($profile->display_name ?? ''));
            if ($displayName !== '') {
                $names[(int) $profile->user_id] = $displayName;
            }
        }

        $withoutName = array_values(array_diff($userIds, array_keys($names)));

        if ($withoutName !== []) {
            // Soft-deleted accounts included: attribution outlives the account,
            // so their username is still the name to show.
            /** @var array<int, User> $users */
            $users = (new User($this->app->db()))->in('id', $withoutName)->findAll();

            foreach ($users as $user) {
                $names[(int) $user->id] = (string) $user->username;
            }
        }

        $prefix = rtrim($this->app->pluginLoader()->routePrefix('pubvana/profiles'), '/');

        $authors = [];
        foreach ($userIds as $userId) {
            $name = $names[$userId] ?? null;

            if ($name === null) {
                continue;
            }

            $authors[$userId] = [
                'name' => $name,
                'url'  => $prefix . '/' . $userId,
            ];
        }

        return $authors;
    }

    /**
     * A stored social URL renders only when it is a full safe http(s) URL.
     */
    protected function safeUrl(?string $value): ?string
    {
        return UrlService::isSafeExternalUrl($value) && trim((string) $value) !== ''
            ? trim((string) $value)
            : null;
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
     * Username for a user id, or empty string when there is no such row.
     *
     * Soft-deleted accounts resolve: attribution outlives the account.
     */
    protected function usernameFor(int $userId): string
    {
        $user = new User($this->app->db());
        $user->eq('id', $userId)->find();

        return $user->isHydrated() ? (string) $user->username : '';
    }
}