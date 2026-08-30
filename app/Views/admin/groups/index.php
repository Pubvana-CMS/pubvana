<?php
/**
 * Group listing - admin page.
 *
 * @var string $pageTitle
 * @var \Enlivenapp\FlightShield\Models\AuthGroup[] $groups
 * @var array<string,int> $userCounts
 */
?>

<div class="d-flex align-items-center justify-content-end mb-4">
    <a href="/admin/groups/create" class="btn btn-primary">
        <i class="ti ti-plus me-1"></i> New Group
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Alias</th>
                    <th>Description</th>
                    <th>Users</th>
                    <th class="w-1">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($groups)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-secondary py-4">No groups found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($groups as $g): ?>
                        <tr>
                            <td>
                                <a href="/admin/groups/<?= (int) $g->id ?>/edit">
                                    <?= htmlspecialchars($g->title) ?>
                                </a>
                            </td>
                            <td><code><?= htmlspecialchars($g->alias) ?></code></td>
                            <td class="text-secondary"><?= htmlspecialchars($g->description ?? '') ?></td>
                            <td>
                                <span class="badge bg-blue-lt">
                                    <?= $userCounts[$g->alias] ?? 0 ?> users
                                </span>
                            </td>
                            <td>
                                <div class="btn-list flex-nowrap">
                                    <a href="/admin/groups/<?= (int) $g->id ?>/edit" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form method="POST" action="/admin/groups/<?= (int) $g->id ?>/delete"
                                          class="d-inline" onsubmit="return confirm('Delete this group? Users in this group will lose its permissions.')">
                                        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
