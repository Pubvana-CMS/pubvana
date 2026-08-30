# Blog

Content module for Pubvana. Blog provides posts, categories, tags, revision history, optional comments, featured posts, view tracking, and preview links.

## Structure

- `Controllers/BlogPublicController.php` - Public rendering (listing, post, category, tag, archive, preview).
- `Controllers/BlogAdminController.php` - Admin CRUD for posts, categories, and tags.
- `Models/Post.php` - ActiveRecord model for the `posts` table.
- `Models/PostRevision.php` - ActiveRecord model for the `post_revisions` table.
- `Models/Category.php`, `Tag.php`, `PostCategory.php`, `PostTag.php` - Taxonomy models.
- `Services/BlogService.php` - Service layer. Registered on the engine as `$app->blog()`.
- `Database/Migrations/` - `posts`, `categories`, `tags`, join tables, and `post_revisions`.

## Routes

Admin routes are registered under `/admin/blog`. Public routes use the `blog` route prefix (`/blog` by default).

| Method | Path | Handler |
|--------|------|---------|
| GET    | `/blog` (admin) | `BlogAdminController::index` |
| GET    | `/blog/create` | `BlogAdminController::create` |
| POST   | `/blog/store` | `BlogAdminController::store` |
| GET    | `/blog/@id/edit` | `BlogAdminController::edit` |
| POST   | `/blog/@id/update` | `BlogAdminController::update` |
| POST   | `/blog/@id/delete` | `BlogAdminController::delete` |
| GET    | `/blog/@id/revisions` | `BlogAdminController::revisions` |
| POST   | `/blog/@id/restore/@revisionId` | `BlogAdminController::restore` |
| GET    | `/blog/categories` | `BlogAdminController::categories` |
| GET    | `/blog/categories/create` | `BlogAdminController::createCategory` |
| POST   | `/blog/categories/store` | `BlogAdminController::storeCategory` |
| GET    | `/blog/categories/@id/edit` | `BlogAdminController::editCategory` |
| POST   | `/blog/categories/@id/update` | `BlogAdminController::updateCategory` |
| POST   | `/blog/categories/@id/delete` | `BlogAdminController::deleteCategory` |
| GET    | `/blog/tags` | `BlogAdminController::tags` |
| POST   | `/blog/tags/@id/delete` | `BlogAdminController::deleteTag` |
| GET    | `/{prefix}` | `BlogPublicController::index` |
| GET    | `/{prefix}/page/@page` | `BlogPublicController::index` |
| GET    | `/{prefix}/category` | `BlogPublicController::categories` |
| GET    | `/{prefix}/category/@slug` | `BlogPublicController::category` |
| GET    | `/{prefix}/tag` | `BlogPublicController::tags` |
| GET    | `/{prefix}/tag/@slug` | `BlogPublicController::tag` |
| GET    | `/{prefix}/preview/@token` | `BlogPublicController::preview` |
| GET    | `/{prefix}/@slug` | `BlogPublicController::show` |

## Revisions

Every create, update, and restore snapshots the previous state into `post_revisions`. Editors review and restore prior versions from `/blog/@id/revisions`.

- A revision records `title`, `content`, `excerpt`, and `status` (not `slug`, which is immutable after creation).
- Restoring a revision writes its fields back to the post and first snapshots the current state, so a restore is itself reversible.
- The history is capped by `max_revisions` (default `15`) in `Config/Config.php`. Older revisions are pruned automatically.

## Preview links

Draft and scheduled posts carry a `preview_token`. The public preview route (`/{prefix}/preview/@token`) renders a post by token without requiring published status, used for draft review before publishing.

## Categories and tags

Posts belong to zero or more categories and tags. Relationships are stored in the `posts_to_categories` and `tags_to_posts` join tables and synchronized through `BlogService`.

## ai_generated

The `ai_generated` column flags a post's origin:

- `0` - Created manually through the admin UI.
- `1` - Created by the AI Assistant plugin.

It is set once at creation and is never changed by later edits. The AI Assistant API exposes the flag in its serialized post output.

## Integrations

Blog registers with adext for:

- **Dashboard** - post count card and recent-posts section.
- **Blocks** - Recent Posts, Categories, Tags, Archive List, and Related Posts.
- **Search** - title/content search results.
- **Comments** - commentable content hosts (`commentable_type` = `post`).
- **Navigation** - published posts as linkable targets.
