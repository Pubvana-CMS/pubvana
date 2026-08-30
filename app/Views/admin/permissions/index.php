<?php
/**
 * Permission listing - admin page.
 *
 * @var string $pageTitle
 * @var \Enlivenapp\FlightShield\Models\AuthPermission[] $permissions
 */
?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Alias</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($permissions)): ?>
                    <tr>
                        <td colspan="3" class="text-center text-secondary py-4">No permissions found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($permissions as $perm): ?>
                        <tr>
                            <td>
                                <code><?= htmlspecialchars($perm->alias) ?></code>
                            </td>
                            <td class="text-secondary"><?= htmlspecialchars($perm->description ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
