# Admin Notifications System Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a dismissible notification system to the admin dashboard that supports system-generated and extension-registered notices.

**Architecture:** Single `admin_notifications` table with an `AdminNotificationModel` for data access. A lightweight `Admin\Notifications` controller handles AJAX dismissal. The dashboard controller loads active notifications and the view renders them as Bootstrap 4 alerts with jQuery-driven dismiss. Global dismissal (not per-user).

**Tech Stack:** CodeIgniter 4.7, MySQL, Bootstrap 4 (SB Admin 2), jQuery, Font Awesome 6

**Spec:** `docs/superpowers/specs/2026-03-25-admin-notifications-design.md`

---

## File Map

| Action | File | Responsibility |
|--------|------|----------------|
| Create | `app/Database/Migrations/2026-03-25-120000_CreateAdminNotificationsTable.php` | Migration for `admin_notifications` table |
| Create | `app/Models/AdminNotificationModel.php` | Model with `getActive()` and `dismiss()` methods |
| Create | `app/Controllers/Admin/Notifications.php` | AJAX dismiss endpoint |
| Modify | `app/Config/Routes.php:54-57` | Add notification dismiss route inside admin group |
| Modify | `app/Controllers/Admin/Dashboard.php` | Load notifications and pass to view |
| Modify | `app/Views/admin/dashboard/index.php` | Render notification alerts + dismiss JS |

---

### Task 1: Migration

**Files:**
- Create: `app/Database/Migrations/2026-03-25-120000_CreateAdminNotificationsTable.php`

- [ ] **Step 1: Create migration file**

```php
<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAdminNotificationsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'source' => [
                'type'       => 'ENUM',
                'constraint' => ['system', 'theme', 'widget', 'plugin'],
                'null'       => false,
            ],
            'source_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => false,
            ],
            'severity' => [
                'type'       => 'ENUM',
                'constraint' => ['info', 'warning', 'error', 'success'],
                'null'       => false,
            ],
            'message' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => false,
            ],
            'action_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'action_label' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'dismissed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('dismissed_at');

        $this->forge->createTable('admin_notifications', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('admin_notifications', true);
    }
}
```

- [ ] **Step 2: Run migration**

Run: `php spark migrate`
Expected: Table `admin_notifications` created successfully.

- [ ] **Step 3: Verify table exists**

Run: `php spark db:table admin_notifications`
Expected: Shows the table structure with all columns matching the spec.

- [ ] **Step 4: Commit**

```bash
git add app/Database/Migrations/2026-03-25-120000_CreateAdminNotificationsTable.php
git commit -m "feat: add admin_notifications migration"
```

---

### Task 2: Model

**Files:**
- Create: `app/Models/AdminNotificationModel.php`

- [ ] **Step 1: Create model file**

```php
<?php

namespace App\Models;

use CodeIgniter\Model;

class AdminNotificationModel extends Model
{
    protected $table         = 'admin_notifications';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'source',
        'source_name',
        'severity',
        'message',
        'action_url',
        'action_label',
        'dismissed_at',
    ];

    /**
     * Return all active (undismissed) notifications, newest first.
     *
     * @return array<\stdClass>
     */
    public function getActive(): array
    {
        return $this->where('dismissed_at', null)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    /**
     * Dismiss a notification by ID.
     * Returns false if not found or already dismissed.
     */
    public function dismiss(int $id): bool
    {
        $notification = $this->find($id);

        if (! $notification || $notification->dismissed_at !== null) {
            return false;
        }

        return $this->update($id, [
            'dismissed_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
```

- [ ] **Step 2: Verify model loads without errors**

Run: `php spark tinker` then type `new \App\Models\AdminNotificationModel();`
Expected: No errors. (Exit with `exit`)

- [ ] **Step 3: Commit**

```bash
git add app/Models/AdminNotificationModel.php
git commit -m "feat: add AdminNotificationModel with getActive and dismiss"
```

---

### Task 3: Controller + Route

**Files:**
- Create: `app/Controllers/Admin/Notifications.php`
- Modify: `app/Config/Routes.php:54-57`

- [ ] **Step 1: Create the Notifications controller**

```php
<?php

namespace App\Controllers\Admin;

use App\Models\AdminNotificationModel;

class Notifications extends BaseAdminController
{
    public function dismiss(int $id)
    {
        $model = new AdminNotificationModel();

        if (! $model->dismiss($id)) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Notification not found.']);
        }

        return $this->response->setJSON(['success' => true]);
    }
}
```

- [ ] **Step 2: Add route**

In `app/Config/Routes.php`, inside the admin group, after the Dashboard route (line 57), add:

```php
    // Notifications
    $routes->post('notifications/dismiss/(:num)', 'Notifications::dismiss/$1');
```

- [ ] **Step 3: Verify route is registered**

Run: `php spark routes | grep notifications`
Expected: Shows `POST admin/notifications/dismiss/([0-9]+)` mapped to `\App\Controllers\Admin\Notifications::dismiss`

- [ ] **Step 4: Commit**

```bash
git add app/Controllers/Admin/Notifications.php app/Config/Routes.php
git commit -m "feat: add notification dismiss endpoint"
```

---

### Task 4: Dashboard Controller Changes

**Files:**
- Modify: `app/Controllers/Admin/Dashboard.php`

- [ ] **Step 1: Add notification loading to Dashboard::index()**

Add the `use` statement at the top of the file, below the existing model imports:

```php
use App\Models\AdminNotificationModel;
```

Inside the `index()` method, before the `return` statement, add:

```php
$notificationModel = new AdminNotificationModel();
$notifications     = $notificationModel->getActive();
```

Then add `'notifications' => $notifications` to the array passed to `adminView()`. The full return becomes:

```php
return $this->adminView('dashboard/index', array_merge($this->baseData('Dashboard', 'dashboard'), [
    'stats'            => $stats,
    'recent_posts'     => $recentPosts,
    'pending_comments' => $pendingComments,
    'update'           => $update,
    'notifications'    => $notifications,
]));
```

- [ ] **Step 2: Verify dashboard still loads**

Open `http://localhost/admin` in a browser. Expected: Dashboard loads without errors, no notifications visible (table is empty).

- [ ] **Step 3: Commit**

```bash
git add app/Controllers/Admin/Dashboard.php
git commit -m "feat: load active notifications in dashboard controller"
```

---

### Task 5: Dashboard View Changes

**Files:**
- Modify: `app/Views/admin/dashboard/index.php`

- [ ] **Step 1: Add notifications area to the view**

After the Page Heading section (after the closing `</div>` of the `d-sm-flex` row, around line 14), insert the notifications block:

```php
<!-- Admin Notifications -->
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

- [ ] **Step 2: Add dismiss JS and update view closing structure**

Replace the current closing lines (lines 151-152):

```php
<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
```

With:

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

- [ ] **Step 3: Insert a test notification to verify rendering**

Run in MySQL or via tinker:

```sql
INSERT INTO admin_notifications (source, source_name, severity, message, action_url, action_label, created_at, updated_at)
VALUES ('system', 'System Message', 'info', 'Welcome to the new notification system!', NULL, NULL, NOW(), NOW());
```

Open `http://localhost/admin`. Expected: Blue info alert appears below the heading, above stat cards, with "System Message:" bold label and the message text. Dismiss button (X) visible on the right.

- [ ] **Step 4: Test dismiss functionality**

Click the X button on the test notification. Expected: Alert fades out and disappears. Refresh the page — notification should not reappear.

Verify in DB: `SELECT dismissed_at FROM admin_notifications WHERE id = 1;` — should show a datetime value.

- [ ] **Step 5: Test all severity levels**

Insert one of each severity to verify styling:

```sql
INSERT INTO admin_notifications (source, source_name, severity, message, action_url, action_label, created_at, updated_at) VALUES
('theme', 'Ember', 'warning', 'A theme update is available.', '/admin/themes', 'Update Now', NOW(), NOW()),
('plugin', 'SEO Tools', 'error', 'Configuration error detected.', '/admin/settings', 'Fix Now', NOW(), NOW()),
('widget', 'Author Bio', 'success', 'Widget installed successfully.', NULL, NULL, NOW(), NOW());
```

Expected:
- Yellow warning alert with "Ember:" label and "Update Now" link
- Red danger alert with "SEO Tools:" label and "Fix Now" link
- Green success alert with "Author Bio:" label, no link

- [ ] **Step 6: Clean up test data and commit**

```sql
DELETE FROM admin_notifications;
```

```bash
git add app/Views/admin/dashboard/index.php
git commit -m "feat: render notifications on dashboard with AJAX dismiss"
```

---

### Task 6: Final Verification

- [ ] **Step 1: Verify empty state**

Open `http://localhost/admin` with no notifications in the DB. Expected: No notification area visible, dashboard looks exactly as before. The `#admin-notifications` div is in the DOM but empty, taking up no space.

- [ ] **Step 2: Verify no other controllers are affected**

The new route (`POST admin/notifications/dismiss/(:num)`) does not conflict with any existing routes. The Dashboard controller changes only add a new variable — existing variables are unchanged. No other controllers call `AdminNotificationModel`.

- [ ] **Step 3: Final commit (if any cleanup needed)**

```bash
git status
```

If clean, no commit needed. If any stray changes, stage and commit with an appropriate message.
