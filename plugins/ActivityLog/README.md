# Activity Log Plugin

Audit trail of admin actions for Pubvana CMS.

## Features

- **Explicit logging API**: Plugins call `$app->activityLog()->log([...])` to record actions
- **Auto-tracking**: Automatically logs admin POST/PUT/DELETE/PATCH routes (configurable)
- **Admin UI**: Filterable, paginated table under Tools → Activity Log
- **Dashboard card**: Shows recent activity count (last 24 hours)
- **Retention**: Configurable retention period (default 365 days)

## Installation

The plugin is included with Pubvana. Enable it at **Settings → Plugins**.

## Configuration

Edit `plugins/ActivityLog/Config/Config.php`:

```php
return [
    'routePrepend' => 'activity-log',
    'track_admin_actions' => true,  // Enable/disable auto-tracking
    'retention_days' => 365,        // Future: cleanup CLI command
];
```

## Usage

### Explicit Logging

```php
$app->activityLog()->log([
    'action'      => 'publish',
    'entity_type' => 'blog_post',
    'entity_id'   => $post->id,
    'entity_name' => $post->title,
    'details'     => ['status' => 'published', 'previous_status' => 'draft'],
]);
```

### Auto-Tracking

When `track_admin_actions` is `true` (default), the plugin automatically logs:
- Content CRUD: blog posts, pages, categories, tags, media
- Redirects and 404 manager actions
- Forms and submissions
- Users, groups, permissions
- Settings changes
- Navigation, themes, regions
- Plugins toggle
- SEO settings
- Comments moderation
- Profiles
- Backups
- Analytics toggle
- Social links

Routes matching `/admin/auth/`, `/admin/assets/`, `/admin/api/`, and `/admin/activity-log` are excluded.

## Database

Table: `activity_logs`

| Column | Type | Description |
|--------|------|-------------|
| id | primary | Auto-increment |
| user_id | integer NULL | FK to users.id |
| user_name | varchar(255) | Snapshot of username |
| action | varchar(50) | create, update, delete, publish, etc. |
| entity_type | varchar(100) | blog_post, page, redirect, user, etc. |
| entity_id | integer NULL | Target entity ID |
| entity_name | varchar(255) | Human-readable name |
| details | text NULL | JSON context |
| ip | varchar(45) | Client IP |
| user_agent | text NULL | Client user agent |
| created_at | datetime | Timestamp |

Indexes: `(user_id)`, `(action)`, `(entity_type, entity_id)`, `(created_at)`

## Permissions

- `activity_log.view` — View activity log (seeded on enable)

## Dashboard Card

Shows count of admin actions in the last 24 hours. Click to navigate to full log.