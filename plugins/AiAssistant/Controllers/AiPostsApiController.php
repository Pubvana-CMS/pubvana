<?php

declare(strict_types=1);

namespace Pubvana\Plugins\AiAssistant\Controllers;

use Pubvana\Plugins\AiAssistant\Models\AiKey;

/**
 * AiPostsApiController - /api/ai/posts/* endpoints.
 *
 * Creates and updates posts through the Blog plugin. Converts submitted
 * markdown to HTML. Attributes AI-created posts to the default author.
 *
 * @package Pubvana\Plugins\AiAssistant\Controllers
 */
class AiPostsApiController extends AiApiController
{
    /**
     * List posts, newest first, across every status.
     */
    public function posts(): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'posts.read');

        $query = $this->app->request()->query;
        $page = max(1, (int) ($query->page ?? 1));
        $perPage = min(100, max(1, (int) ($query->per_page ?? 25)));
        $status = $query->status ?? null;
        if ($status !== null && !in_array($status, ['draft', 'published', 'scheduled'], true)) {
            $this->log($key, 'error', 'post', null, "Invalid status '{$status}'.");
            $this->fail(422, 'status filter must be one of: draft, published, scheduled.');
        }
        $search = $this->app->ai()->searchParam($query);

        $result = $this->app->ai()->listPostsForApi(
            $page,
            $perPage,
            $status !== null ? (string) $status : null,
            $search
        );

        $detail = 'Listed posts';
        if ($status !== null) {
            $detail .= " (status: {$status})";
        }
        if ($search !== null) {
            $detail .= " (search: {$search})";
        }
        $this->log($key, 'ok', 'post', null, $detail . '.');
        $this->ok($result);
    }

    /**
     * Fetch one post by slug, serving content as markdown.
     */
    public function post(string $slug): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'posts.read');

        $post = $this->app->ai()->findPostForApi($slug);
        if ($post === null) {
            $this->log($key, 'error', 'post', null, "Post '{$slug}' not found.");
            $this->fail(404, 'Post not found.');
        }

        $this->log($key, 'ok', 'post', (int) $post->id, "Fetched post '{$slug}'.");
        $this->ok($this->app->ai()->serializePost($post));
    }

    /**
     * Create a post as a draft by default.
     */
    public function createPost(): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'posts.create');

        $payload = $this->payload();

        $title = trim((string) ($payload['title'] ?? ''));
        if ($title === '') {
            $this->log($key, 'error', 'post', null, 'Missing title.');
            $this->fail(422, 'title is required.');
        }

        $content = $this->app->ai()->resolveContent($payload);
        if ($content === null) {
            $this->log($key, 'error', 'post', null, 'Missing content.');
            $this->fail(422, 'Provide content_md (markdown) or content (HTML).');
        }

        [$status, $publishedAt] = $this->resolvePostStatus($key, $payload, null);

        $slug = $this->app->slugify((string) ($payload['slug'] ?? '') ?: $title);
        if ($slug === '') {
            $this->log($key, 'error', 'post', null, 'Slug could not be generated.');
            $this->fail(422, 'A URL slug could not be generated from the title.');
        }
        if ($this->svc('blog')->postSlugExists($slug)) {
            $slug .= '-' . time();
        }

        $tags = $payload['tags'] ?? '';
        if (is_array($tags)) {
            $tags = implode(', ', array_map('strval', $tags));
        } else {
            $tags = (string) $tags;
        }
        $categories = $this->app->ai()->categoryIds($payload['categories'] ?? []);

        try {
            $post = $this->svc('blog')->createPost([
                'title'            => $title,
                'slug'             => $slug,
                'content'          => $content,
                'excerpt'          => $this->app->ai()->nullableString($payload['excerpt'] ?? null),
                'status'           => $status,
                'featured_image'   => $this->app->ai()->nullableString($payload['featured_image'] ?? null),
                'media_id'         => !empty($payload['media_id']) ? (int) $payload['media_id'] : null,
                'published_at'     => $publishedAt,
                'is_featured'      => !empty($payload['is_featured']) ? 1 : 0,
                'allow_comments'   => !empty($payload['allow_comments']) ? 1 : 0,
                'ai_generated'     => 1,
                'purify_content'   => true,
            ], $this->app->ai()->defaultAuthorId());

            if ($categories !== []) {
                $this->svc('blog')->syncPostCategories((int) $post->id, $categories);
            }
            if ($tags !== '') {
                $this->svc('blog')->syncPostTags((int) $post->id, $tags);
            }
        } catch (\Throwable $e) {
            $this->log($key, 'error', 'post', null, $e->getMessage());
            $this->fail(500, 'The post could not be created: ' . $e->getMessage());
        }

        $this->app->ai()->saveSeo('post', (int) $post->id, $payload);

        $this->log($key, 'ok', 'post', (int) $post->id, "Created post #{$post->id}.");
        $this->ok([
            'post'  => $this->app->ai()->serializePost($post),
            'url'   => $this->app->pluginLoader()->routePrefix('pubvana/blog') . '/' . $slug,
            'id'    => (int) $post->id,
        ]);
    }

    /**
     * Partially update a post. Omitting status leaves the current state.
     */
    public function updatePost(string $id): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'posts.update');

        $payload = $this->payload();
        $blog = $this->svc('blog');

        $existing = $blog->findPost((int) $id);
        if ($existing === null) {
            $this->log($key, 'error', 'post', (int) $id, 'Post not found.');
            $this->fail(404, 'Post not found.');
        }

        [$status, $publishedAt] = $this->resolvePostStatus($key, $payload, $existing);

        $title = array_key_exists('title', $payload) ? (string) $payload['title'] : (string) $existing->title;
        if (trim($title) === '') {
            $this->log($key, 'error', 'post', (int) $id, 'Empty title.');
            $this->fail(422, 'title must not be empty.');
        }

        $content = $this->app->ai()->resolveContent($payload);
        if ($content === null) {
            $content = (string) ($existing->content ?? '');
        }

        $update = [
            'title'        => $title,
            'content'      => $content,
            'status'       => $status,
            'published_at' => $publishedAt,
        ];
        if (array_key_exists('excerpt', $payload)) {
            $update['excerpt'] = $this->app->ai()->nullableString($payload['excerpt'] ?? null);
        }
        if (array_key_exists('featured_image', $payload)) {
            $update['featured_image'] = $this->app->ai()->nullableString($payload['featured_image'] ?? null);
        }
        if (array_key_exists('media_id', $payload)) {
            $update['media_id'] = !empty($payload['media_id']) ? (int) $payload['media_id'] : null;
        }
        if (array_key_exists('is_featured', $payload)) {
            $update['is_featured'] = !empty($payload['is_featured']) ? 1 : 0;
        }
        if (array_key_exists('allow_comments', $payload)) {
            $update['allow_comments'] = !empty($payload['allow_comments']) ? 1 : 0;
        }
        $update['purify_content'] = true;

        try {
            $post = $blog->updatePost((int) $id, $update, $this->app->ai()->defaultAuthorId());

            if (array_key_exists('categories', $payload)) {
                $blog->syncPostCategories((int) $id, $this->app->ai()->categoryIds($payload['categories']));
            }
            if (array_key_exists('tags', $payload)) {
                $tags = $payload['tags'];
                if (is_array($tags)) {
                    $tags = implode(', ', array_map('strval', $tags));
                }
                $blog->syncPostTags((int) $id, (string) $tags);
            }
        } catch (\Throwable $e) {
            $this->log($key, 'error', 'post', (int) $id, $e->getMessage());
            $this->fail(500, 'The post could not be updated: ' . $e->getMessage());
        }

        $this->app->ai()->saveSeo('post', (int) $id, $payload);

        $this->log($key, 'ok', 'post', (int) $id, "Updated post #{$id}.");
        $this->ok($this->app->ai()->serializePost($post));
    }

    /**
     * Delete a post.
     */
    public function deletePost(string $id): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'posts.delete');

        $post = $this->svc('blog')->findPost((int) $id);
        if ($post === null) {
            $this->log($key, 'error', 'post', (int) $id, 'Post not found.');
            $this->fail(404, 'Post not found.');
        }

        $this->svc('blog')->deletePost((int) $id);
        $this->log($key, 'ok', 'post', (int) $id, "Deleted post #{$id}.");
        $this->ok(['deleted' => true, 'id' => (int) $id]);
    }

    /**
     * List blog post tags.
     */
    public function tags(): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'posts.tags.read');

        $tags = [];
        foreach ($this->svc('blog')->listTags() as $tag) {
            $tags[] = [
                'id'   => (int) $tag->id,
                'name' => (string) $tag->name,
                'slug' => (string) $tag->slug,
            ];
        }

        $this->log($key, 'ok', 'tag', null, 'Listed tags.');
        $this->ok($tags);
    }

    /**
     * List blog post categories.
     */
    public function categories(): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'posts.categories.read');

        $categories = [];
        foreach ($this->svc('blog')->listCategories() as $category) {
            $categories[] = [
                'id'          => (int) $category->id,
                'name'        => (string) $category->name,
                'slug'        => (string) $category->slug,
                'parent_id'   => $category->parent_id !== null ? (int) $category->parent_id : null,
            ];
        }

        $this->log($key, 'ok', 'category', null, 'Listed categories.');
        $this->ok($categories);
    }

    /**
     * Pick the post's target status and published_at, checking grants.
     *
     * State changes are gated both ways: moving to published/scheduled
     * needs the matching grant, and moving out of a live state down to
     * draft takes the grant for the state being left. A draft that stays
     * draft needs nothing.
     *
     * @param array<string, mixed> $payload Posted payload
     * @param \Pubvana\Plugins\Blog\Models\Post|null $existing Post being updated, or null when creating
     * @return array{0: string, 1: ?string} [status, published_at]
     */
    protected function resolvePostStatus(AiKey $key, array $payload, $existing): array
    {
        // Omitting status means "leave the state alone": the target starts
        // at the existing status, so a bare content edit on a live post
        // keeps its live state. Grants fire on real changes only;
        // re-applying the current status is not a transition.
        $explicit = array_key_exists('status', $payload);
        $status = (string) ($payload['status'] ?? ($existing !== null ? (string) $existing->status : 'draft'));
        $transition = $explicit && ($existing === null || (string) $existing->status !== $status);
        $publishedAt = null;

        if ($status === 'published') {
            if ($transition) {
                $this->requireGrant($key, 'posts.publish');
            }
            $publishedAt = $this->now();
            if ($existing !== null && (string) $existing->status === 'published' && $existing->published_at !== null) {
                $publishedAt = (string) $existing->published_at;
            }
        } elseif ($status === 'scheduled') {
            if ($transition) {
                $this->requireGrant($key, 'posts.schedule');
            }
            $publishOn = (string) ($payload['publish_on'] ?? ($existing !== null ? (string) ($existing->published_at ?? '') : ''));
            if ($publishOn === '' || !$this->isDatetime($publishOn)) {
                $this->log($key, 'error', 'post', $existing !== null ? (int) $existing->id : null, 'Invalid publish_on.');
                $this->fail(422, "status 'scheduled' requires a valid publish_on (e.g. 2026-09-01 09:00:00).");
            }
            $ts = strtotime($publishOn);
            $publishedAt = $ts !== false ? date('Y-m-d H:i:s', $ts) : null;
        } elseif ($status === 'draft') {
            if ($explicit && $existing !== null && in_array((string) $existing->status, ['published', 'scheduled'], true)) {
                $grant = $this->app->ai()->demoteGrant('posts', (string) $existing->status);
                if ($grant !== null) {
                    $this->requireGrant($key, $grant);
                }
            }
        } else {
            $this->log($key, 'error', 'post', $existing !== null ? (int) $existing->id : null, "Invalid status '{$status}'.");
            $this->fail(422, 'status must be one of: draft, published, scheduled.');
        }

        return [$status, $publishedAt];
    }

    private function isDatetime(string $value): bool
    {
        return strtotime($value) !== false;
    }

    private function now(): string
    {
        return (new \DateTimeImmutable())->format('Y-m-d H:i:s');
    }
}