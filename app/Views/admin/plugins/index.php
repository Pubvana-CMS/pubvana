<?php
/**
 * Plugins admin page.
 *
 * Lists every discovered plugin (local + vendor) with its database-backed
 * enablement state. Toggling a plugin's switch autosaves immediately
 * (each row posts its own small form). Enabling a plugin runs its pending
 * migrations/seeds on save (see PluginsController::save()); a disabled
 * plugin runs nothing.
 *
 * Priority is read-only — it reflects the load order decided at
 * installation/first discovery (default 50) and is not user-editable.
 *
 * `required` plugins (sessions/shield/csrf) are locked — no form, the switch
 * is decorative; the server-side invariant in PluginsController::save()
 * never lets them disable.
 *
 * @var array $plugins Rows from PluginsController::index()
 * @var mixed $flash  Session flash message
 */
?>
<?php if (!empty($flash)): ?>
    <div class="alert alert-<?= str_contains((string) $flash, 'left disabled') ? 'danger' : 'info' ?> mb-3">
        <?= htmlspecialchars((string) $flash, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title d-flex align-items-center gap-2">
            <i class="ti ti-puzzle text-primary"></i>
            Plugins
        </h3>
        <div class="card-subtitle text-secondary">
            Toggling a plugin saves instantly. Enabling runs its migrations immediately; disabled plugins run nothing.
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Plugin</th>
                    <th class="text-center" style="width:110px;">Version</th>
                    <th class="text-center" style="width:110px;">Enabled</th>
                    <th class="text-center" style="width:100px;">Priority</th>
                    <th class="text-center" style="width:110px;">Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($plugins as $plugin): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar avatar-sm">
                                <i class="ti ti-puzzle"></i>
                            </span>
                            <div>
                                <div class="fw-semibold"><?= htmlspecialchars($plugin['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-secondary text-sm">
                                    <?= htmlspecialchars($plugin['id'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="text-center">
                        <?php if (!empty($plugin['version'])): ?>
                            <span class="text-secondary fw-semibold"><?= htmlspecialchars($plugin['version'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php else: ?>
                            <span class="text-secondary">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($plugin['required']): ?>
                            <!-- Required: locked. The toggle is decorative — the
                                 server-side invariant in PluginsController::save()
                                 never disables required plugins. -->
                            <label class="form-check form-switch mb-0 d-flex justify-content-center" title="Required core plugin — cannot be disabled">
                                <input class="form-check-input" type="checkbox" checked disabled>
                            </label>
                        <?php else: ?>
                            <form method="post" action="/admin/plugins/save" class="d-inline-block">
                                <?= csrf_field() ?>
                                <input type="hidden" name="plugins[<?= htmlspecialchars($plugin['id'], ENT_QUOTES, 'UTF-8') ?>][enabled]" value="0">
                                <label class="form-check form-switch mb-0 d-flex justify-content-center">
                                    <input class="form-check-input" type="checkbox"
                                           name="plugins[<?= htmlspecialchars($plugin['id'], ENT_QUOTES, 'UTF-8') ?>][enabled]"
                                           value="1"
                                           <?= $plugin['enabled'] ? 'checked' : '' ?>
                                           onchange="this.form.requestSubmit()"
                                           title="<?= $plugin['enabled'] ? 'Disable this plugin' : 'Enable this plugin and run its migrations' ?>">
                                </label>
                            </form>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <span class="text-secondary fw-semibold"><?= (int) $plugin['priority'] ?></span>
                    </td>
                    <td class="text-center">
                        <?php if ($plugin['required']): ?>
                            <span class="badge bg-yellow-lt text-yellow">Required</span>
                        <?php elseif ($plugin['enabled']): ?>
                            <span class="badge bg-green-lt text-success">Enabled</span>
                        <?php else: ?>
                            <span class="badge bg-secondary-lt text-secondary">Disabled</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>