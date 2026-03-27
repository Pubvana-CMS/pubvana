# Admin Dashboard Notification System — Design Spec

## Overview

Add a notification system to the admin dashboard that supports system-generated notices and notices registered by extensions (themes, widgets, plugins). Notifications appear as dismissible Bootstrap alerts above the stat cards on the dashboard. Dismissal is global (any admin dismissing removes for everyone) via AJAX.

## Database Schema

New table: `admin_notifications`

| Column | Type | Constraints |
|---|---|---|
| id | INT unsigned | PK, AUTO_INCREMENT |
| source | ENUM('system','theme','widget','plugin') | NOT NULL — category of origin |
| source_name | VARCHAR(150) | NOT NULL — specific name (e.g., "System Message", "Ember") |
| severity | ENUM('info','warning','error','success') | NOT NULL — maps to Bootstrap alert classes |
| message | VARCHAR(500) | NOT NULL — the notice text |
| action_url | VARCHAR(255) | NULLABLE — optional link |
| action_label | VARCHAR(100) | NULLABLE — button text; defaults to "View" in the view if action_url is present but label is null |
| dismissed_at | DATETIME | NULLABLE — NULL means active, set means dismissed |
| created_at | DATETIME | NULLABLE — CI4 managed |
| updated_at | DATETIME | NULLABLE — CI4 managed |

Indexes:
- `dismissed_at` — for the active notifications query

Migration file: `2026-03-25-120000_CreateAdminNotificationsTable.php`
Uses `createTable('admin_notifications', true)` for idempotency (project convention).
Implements `down()` with `$this->forge->dropTable('admin_notifications', true)`.

## Model: AdminNotificationModel

- File: `app/Models/AdminNotificationModel.php`
- Table: `admin_notifications`
- Return type: `object` (stdClass)
- `useTimestamps = true`
- Allowed fields: source, source_name, severity, message, action_url, action_label, dismissed_at

### Methods

**`getActive(): array`**
Returns an array of stdClass objects — all notifications where `dismissed_at IS NULL`, ordered by `created_at DESC`.

**`dismiss(int $id): bool`**
Finds the notification by ID. If not found or already dismissed (`dismissed_at IS NOT NULL`), returns false. Otherwise sets `dismissed_at` to current datetime and returns true.

## Controller: Admin\Notifications

- File: `app/Controllers/Admin/Notifications.php`
- Extends `BaseAdminController` (`App\Controllers\Admin\BaseAdminController`)

### Methods

**`dismiss(int $id)`**
- Accepts POST only
- Loads `AdminNotificationModel`, calls `dismiss($id)`
- If `dismiss()` returns false (not found or already dismissed), returns `$this->response->setStatusCode(404)->setJSON(['error' => 'Notification not found.'])`
- On success: returns `$this->response->setJSON(['success' => true])`

## Route

Added inside the existing admin route group (inherits `admin_auth` + `totp` filters):

```
POST admin/notifications/dismiss/(:num) → Notifications::dismiss/$1
```

## Dashboard Changes

### Controller: Admin\Dashboard

- Load `AdminNotificationModel`
- Call `getActive()` and pass `$notifications` to the view (defaults to empty array `[]` if model fails)

### View: admin/dashboard/index.php

**Layout order (top to bottom):**
1. Dashboard heading + New Post button (existing, unchanged)
2. Notifications area (new)
3. Stat cards (existing, unchanged)
4. Recent posts + Pending comments (existing, unchanged)

**Notifications area markup:**

```php
<div id="admin-notifications">
    <?php foreach ($notifications as $notification): ?>
    <?php $alertClass = $notification->severity === 'error' ? 'danger' : $notification->severity; ?>
    <div class="alert alert-<?= $alertClass ?> alert-dismissible fade show mb-2" role="alert">
        <strong><?= esc($notification->source_name) ?>:</strong>
        <?= esc($notification->message) ?>
        <?php if ($notification->action_url): ?>
            <a href="<?= esc($notification->action_url) ?>" class="alert-link">
                <?= esc($notification->action_label ?? 'View') ?>
            </a>
        <?php endif; ?>
        <button type="button" class="btn-close-notification close" data-id="<?= $notification->id ?>" aria-label="Dismiss">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endforeach; ?>
</div>
```

Notes:
- The `error` severity maps to Bootstrap's `alert-danger` class (not `alert-error` which doesn't exist). The `$alertClass` variable handles this transformation.
- The dismiss button uses class `btn-close-notification` instead of relying solely on Bootstrap's `.close` class to avoid Bootstrap's built-in `[data-dismiss="alert"]` behavior interfering. The `.close` class is kept for Bootstrap 4 styling only.

**Severity to Bootstrap class mapping:**
- info → `alert-info`
- warning → `alert-warning`
- error → `alert-danger`
- success → `alert-success`

### JavaScript and view closing structure

The current dashboard view uses a single `ob_start()` / `ob_get_clean()` pair and passes `$content` to the layout. To add `$extra_scripts`, the view's closing section changes from:

```php
<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
```

To:

```php
<?php $content = ob_get_clean(); ?>

<?php ob_start(); ?>
<script>
$(document).on('click', '.btn-close-notification[data-id]', function() {
    var btn = $(this);
    var alertEl = btn.closest('.alert');
    var id = btn.data('id');

    var formData = new FormData();
    formData.append('csrf_test_name', '<?= csrf_hash() ?>');

    $.ajax({
        url: '<?= base_url("admin/notifications/dismiss") ?>/' + id,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function() {
            alertEl.fadeOut(300, function() { $(this).remove(); });
        }
    });
});
</script>
<?php $extra_scripts = ob_get_clean(); ?>

<?= view($layout, array_merge(get_defined_vars(), ['content' => $content, 'extra_scripts' => $extra_scripts])) ?>
```

Notes:
- `ob_start()` has no assignment (it returns a boolean, not the buffer content)
- The assignment happens only on `ob_get_clean()` which returns the captured output
- `$extra_scripts` is passed explicitly to the layout in the `view()` call
- CSRF token sent as FormData field (`csrf_test_name`) — the working pattern in this codebase
- On failure: no-op (alert stays visible)

## What This Does NOT Include

- No charts or analytics on the dashboard (analytics stays premium)
- No activity feed on the dashboard
- No changes to existing stat cards, recent posts, or pending comments
- No per-user dismissal (global dismiss only)
- No admin UI for managing notifications (extensions and system write directly to the table)
