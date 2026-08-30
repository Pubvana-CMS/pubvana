<?php
/**
 * General settings page - tabbed.
 *
 * One tab per admin.settings contribution, sorted by priority. ALL tabs
 * live inside a single form: inactive panes stay in the DOM (hidden via
 * Alpine) so one Save writes every declared setting atomically.
 *
 * Field names use the settings[KEY] array wrapper so PHP does not mangle
 * dots in top-level POST keys. save() whitelists against declarations.
 *
 * @var string $pageTitle
 * @var array<int, array{label: string, description: string, fields: array<int, array>}> $tabs
 * @var bool   $saved  Legacy query-flag banner (?saved=1)
 * @var string|null $flash One-shot flash message from the session
 */

/** Render options for a select field: assoc label=>value or flat list */
$selectOptions = function (array $options): array {
    $out = [];
    foreach ($options as $key => $value) {
        if (is_int($key)) {
            $out[(string) $value] = (string) $key;
        } else {
            $out[$value] = $key;
        }
    }
    return $out;
};
?>

<div x-data="{ tab: 'tab-0' }">

    <?php if ($flash !== null || !empty($saved)): ?>
        <div class="alert alert-success alert-dismissible" role="alert">
            <div class="d-flex">
                <div>
                    <i class="ti ti-circle-check icon alert-icon"></i>
                </div>
                <div>
                    <?= htmlspecialchars($flash ?? 'Settings saved.') ?>
                </div>
            </div>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="Close"></a>
        </div>
    <?php endif; ?>

    <?php if (empty($tabs)): ?>
        <div class="card">
            <div class="card-body text-center text-secondary py-5">
                <i class="ti ti-settings mb-2" style="font-size: 2rem;"></i>
                <p class="mb-0">No settings have been registered.</p>
            </div>
        </div>
    <?php else: ?>

        <form method="POST" action="/admin/settings/save" autocomplete="off">
            <?= csrf_field() ?>

            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <?php foreach ($tabs as $index => $tab): ?>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link <?= $index === 0 ? 'active' : '' ?>"
                                   :class="tab === 'tab-<?= $index ?>' ? 'active' : ''"
                                   href="#tab-<?= $index ?>"
                                   @click.prevent="tab = 'tab-<?= $index ?>'"
                                   role="tab">
                                    <?= htmlspecialchars($tab['label']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="card-body">
                    <?php foreach ($tabs as $index => $tab): ?>
                        <div x-show="tab === 'tab-<?= $index ?>'" role="tabpanel">
                            <?php if (!empty($tab['description'])): ?>
                                <p class="text-secondary small"><?= htmlspecialchars($tab['description']) ?></p>
                            <?php endif; ?>

                            <?php foreach ($tab['fields'] as $field): ?>
                                <?php
                                $key = $field['key'];
                                $value = $field['value'] ?? null;
                                $id = 'field-' . preg_replace('/[^A-Za-z0-9_]/', '_', $key);
                                ?>
                                <div class="mb-3">
                                    <?php if (($field['type']) === 'checkbox'): ?>
                                        <label class="form-check form-switch">
                                            <input class="form-check-input" type="hidden"
                                                   name="settings[<?= htmlspecialchars($key) ?>]" value="0">
                                            <input class="form-check-input" type="checkbox"
                                                   id="<?= $id ?>"
                                                   name="settings[<?= htmlspecialchars($key) ?>]"
                                                   value="1"
                                                <?= $value ? 'checked' : '' ?>>
                                            <span class="form-check-label">
                                                <?= htmlspecialchars($field['label']) ?>
                                            </span>
                                        </label>
                                    <?php else: ?>
                                        <label class="form-label" for="<?= $id ?>">
                                            <?= htmlspecialchars($field['label']) ?>
                                        </label>

                                        <?php if ($field['type'] === 'textarea'): ?>
                                            <textarea class="form-control" id="<?= $id ?>" rows="4"
                                                      name="settings[<?= htmlspecialchars($key) ?>]"><?= htmlspecialchars((string) $value) ?></textarea>
                                        <?php elseif ($field['type'] === 'select'): ?>
                                            <select class="form-select" id="<?= $id ?>"
                                                    name="settings[<?= htmlspecialchars($key) ?>]">
                                                <?php foreach ($selectOptions($field['options'] ?? []) as $optLabel => $optValue): ?>
                                                    <option value="<?= htmlspecialchars($optValue) ?>"
                                                        <?= ((string) $value === (string) $optValue) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($optLabel) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php else: ?>
                                            <?php
                                            $inputType = in_array($field['type'], ['email', 'number'], true)
                                                ? $field['type']
                                                : 'text';
                                            ?>
                                            <input type="<?= $inputType ?>" class="form-control" id="<?= $id ?>"
                                                   name="settings[<?= htmlspecialchars($key) ?>]"
                                                   value="<?= htmlspecialchars((string) $value) ?>">
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php if (!empty($field['description'])): ?>
                                        <small class="form-hint text-secondary">
                                            <?= htmlspecialchars($field['description']) ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i>Save Settings
                    </button>
                </div>
            </div>
        </form>

    <?php endif; ?>

</div>
