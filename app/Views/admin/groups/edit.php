<?php
/**
 * Edit group - admin form with permission checkboxes.
 *
 * @var string $pageTitle
 * @var \Enlivenapp\FlightShield\Models\AuthGroup $group
 * @var \Enlivenapp\FlightShield\Models\AuthPermission[] $allPermissions
 * @var string[] $assignedPerms
 */
?>

<div class="d-flex align-items-center justify-content-end mb-4">
    <a href="/admin/groups" class="btn btn-outline-secondary">Back</a>
</div>

<form method="POST" action="/admin/groups/<?= (int) $group->id ?>/update">
    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Group Details</h3>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Alias</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($group->alias) ?>" readonly>
                        <div class="form-hint">The alias cannot be changed after creation.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="title">Title</label>
                        <input type="text" name="title" id="title" class="form-control"
                               value="<?= htmlspecialchars($group->title) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="description">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3"><?= htmlspecialchars($group->description ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Permissions</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($allPermissions)): ?>
                        <p class="text-secondary">No permissions defined yet. Create permissions first.</p>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($allPermissions as $perm): ?>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-check mb-2">
                                        <input type="checkbox" name="permissions[]" value="<?= htmlspecialchars($perm->alias) ?>"
                                               class="form-check-input"
                                               <?= in_array($perm->alias, $assignedPerms, true) ? 'checked' : '' ?>>
                                        <span class="form-check-label">
                                            <strong><?= htmlspecialchars($perm->alias) ?></strong>
                                            <?php if (!empty($perm->description)): ?>
                                                <br><small class="text-secondary"><?= htmlspecialchars($perm->description) ?></small>
                                            <?php endif; ?>
                                        </span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Info</h3>
                </div>
                <div class="card-body">
                    <div class="text-secondary small mb-2">
                        Created: <?= htmlspecialchars($group->created_at ?? 'Unknown') ?>
                    </div>
                    <?php if (!empty($group->updated_at)): ?>
                        <div class="text-secondary small">
                            Updated: <?= htmlspecialchars($group->updated_at) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100">Update Group</button>
        </div>
    </div>
</form>
