# Pages

Static pages for Pubvana. Pages are simple content containers (About, Contact, Terms, etc.) with SEO metadata, draft/published status, revision history, optional comments, and soft-delete support.

## Structure

- `Controllers/PagesPublicController.php` - Public rendering (`/page/@slug`).
- `Controllers/PagesAdminController.php` - Admin CRUD and revision management (`/admin/pages`).
- `Models/Page.php` - ActiveRecord model for the `pages` table.
- `Models/PageRevision.php` - ActiveRecord model for the `pages_revisions` table.
- `Services/PagesService.php` - Service layer. Registered on the engine as `$app->pages()`.
- `Database/Migrations/` - `pages` and `pages_revisions` tables.

## Routes

| Method | Path | Handler |
|--------|------|---------|
| GET    | `/page` | `PagesPublicController::index` (redirects to homepage) |
| GET    | `/page/@slug` | `PagesPublicController::view` |
| GET    | `/admin/pages` | `PagesAdminController::index` |
| GET    | `/admin/pages/create` | `PagesAdminController::create` |
| POST   | `/admin/pages/store` | `PagesAdminController::store` |
| GET    | `/admin/pages/@id/edit` | `PagesAdminController::edit` |
| POST   | `/admin/pages/@id/update` | `PagesAdminController::update` |
| POST   | `/admin/pages/@id/delete` | `PagesAdminController::delete` |
| GET    | `/admin/pages/@id/revisions` | `PagesAdminController::revisions` |
| POST   | `/admin/pages/@id/restore/@revisionId` | `PagesAdminController::restore` |

## Revisions

Every create, update, and restore snapshots the previous state into `pages_revisions`. Editors review and restore prior versions from `/admin/pages/@id/revisions`.

- A revision records `title`, `content`, `status`, and `allow_comments` (not `slug`, which is immutable after creation).
- Restoring a revision writes its fields back to the page and first snapshots the current state, so a restore is itself reversible.
- The history is capped by `max_revisions` (default `15`) in `Config/Config.php`. Older revisions are pruned automatically.

## ai_generated

The `ai_generated` column flags a page's origin:

- `0` - Created manually through the admin UI.
- `1` - Created by the AI Assistant plugin.

It is set once at creation and is never changed by later edits. The AI Assistant API exposes the flag in its serialized page output.

## Integrations

Pages registers with adext for:

- **Dashboard** - page count card and a recent-pages section.
- **Navigation** - published pages as linkable targets.
- **Search** - title/slug/content search results.
- **Comments** - commentable content hosts (`commentable_type` = `page`).
