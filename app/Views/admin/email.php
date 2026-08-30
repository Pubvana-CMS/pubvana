<?php
/**
 * Email settings page (Tools > Email) - standalone, not a tab.
 *
 * @var string $pageTitle
 * @var array<int, array<string, mixed>> $fields Resolved field definitions
 * @var \Pubvana\Models\Mail[] $recent Recent send attempts, newest first
 * @var int $sentCount Total successful sends
 * @var string|null $flash One-shot save message
 * @var array{ok: bool, debug: string, error: ?string}|null $test Last test result
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

<?php if ($flash !== null): ?>
    <div class="alert alert-success alert-dismissible" role="alert">
        <div class="d-flex">
            <div>
                <i class="ti ti-circle-check icon alert-icon"></i>
            </div>
            <div><?= htmlspecialchars($flash) ?></div>
        </div>
        <a class="btn-close" data-bs-dismiss="alert" aria-label="Close"></a>
    </div>
<?php endif; ?>

<?php if ($test !== null && !empty($test['ok'])): ?>
    <div class="alert alert-success alert-dismissible" role="alert">
        <div class="d-flex">
            <div>
                <i class="ti ti-circle-check icon alert-icon"></i>
            </div>
            <div>Test email sent successfully.<?= htmlspecialchars((string) ($test['error'] ?? '')) ?></div>
        </div>
        <a class="btn-close" data-bs-dismiss="alert" aria-label="Close"></a>
    </div>
<?php elseif ($test !== null): ?>
    <div class="alert alert-danger alert-dismissible" role="alert">
        <div class="d-flex">
            <div>
                <i class="ti ti-alert-triangle icon alert-icon"></i>
            </div>
            <div>
                <strong>Test email failed.</strong>
                <?= htmlspecialchars((string) ($test['error'] ?? 'Unknown SMTP error')) ?>
                <?php if (!empty($test['debug'])): ?>
                    <pre class="mb-0 mt-2 p-2 bg-dark text-white rounded small"
                         style="max-height: 300px; overflow-y: auto;"><?= htmlspecialchars($test['debug']) ?></pre>
                <?php endif; ?>
            </div>
        </div>
        <a class="btn-close" data-bs-dismiss="alert" aria-label="Close"></a>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">SMTP Settings</h3>
            </div>
            <form method="POST" action="/admin/email/save" autocomplete="off">
                <?= csrf_field() ?>
                <div class="card-body">
                    <?php foreach ($fields as $field): ?>
                        <?php
                        $key = $field['key'];
                        $value = $field['value'] ?? null;
                        $id = 'field-' . preg_replace('/[^A-Za-z0-9_]/', '_', $key);
                        $type = $field['type'] ?? 'text';
                        ?>
                        <div class="mb-3">
                            <?php if ($type === 'checkbox'): ?>
                                <label class="form-check form-switch">
                                    <input class="form-check-input" type="hidden"
                                           name="settings[<?= htmlspecialchars($key) ?>]" value="0">
                                    <input class="form-check-input" type="checkbox"
                                           id="<?= $id ?>"
                                           name="settings[<?= htmlspecialchars($key) ?>]"
                                           value="1" <?= $value ? 'checked' : '' ?>>
                                    <span class="form-check-label"><?= htmlspecialchars($field['label']) ?></span>
                                </label>
                            <?php else: ?>
                                <label class="form-label" for="<?= $id ?>">
                                    <?= htmlspecialchars($field['label']) ?>
                                </label>

                                <div class="input-group">
                                    <?php if ($type === 'password'): ?>
                                        <input type="password" class="form-control" id="<?= $id ?>"
                                               name="settings[<?= htmlspecialchars($key) ?>]"
                                               value="<?= htmlspecialchars((string) $value) ?>"
                                               placeholder="<?= htmlspecialchars((string) ($field['placeholder'] ?? '')) ?>"
                                               autocomplete="new-password">
                                        <button type="button" class="btn btn-outline-secondary"
                                                data-toggle-password="#<?= $id ?>">
                                            <i class="ti ti-eye"></i>
                                        </button>
                                    <?php elseif ($type === 'textarea'): ?>
                                        <textarea class="form-control" id="<?= $id ?>" rows="4"
                                                  name="settings[<?= htmlspecialchars($key) ?>]"><?= htmlspecialchars((string) $value) ?></textarea>
                                    <?php elseif ($type === 'select'): ?>
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
                                        $inputType = in_array($type, ['email', 'number'], true) ? $type : 'text';
                                        ?>
                                        <input type="<?= $inputType ?>" class="form-control" id="<?= $id ?>"
                                               name="settings[<?= htmlspecialchars($key) ?>]"
                                               value="<?= htmlspecialchars((string) $value) ?>">
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($field['description'])): ?>
                                <small class="form-hint text-secondary">
                                    <?= htmlspecialchars($field['description']) ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i>Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Send Test Email</h3>
            </div>
            <form method="POST" action="/admin/email/test">
                <?= csrf_field() ?>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="test_to">Recipient</label>
                        <input type="email" class="form-control" id="test_to" name="test_to" required
                               placeholder="e.g. you@example.com">
                        <small class="form-hint text-secondary">Sends a probe message through the configured SMTP server.</small>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="ti ti-send me-1"></i>Send Test Email
                    </button>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Sends</h3>
            </div>
            <?php if (empty($recent)): ?>
                <div class="card-body text-center text-secondary py-4">
                    No mail has been sent yet.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Recipient</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Sent</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent as $row): ?>
                                <tr>
                                    <td class="text-truncate" style="max-width: 10rem;"><?= htmlspecialchars($row->to_address) ?></td>
                                    <td class="text-truncate" style="max-width: 8rem;" title="<?= htmlspecialchars($row->subject) ?>">
                                        <?= htmlspecialchars($row->subject) ?>
                                    </td>
                                    <td>
                                        <?php if ($row->status === 'sent'): ?>
                                            <span class="badge bg-success-lt">Sent</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-lt">Failed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-secondary small"><?= htmlspecialchars((string) $row->sent_at) ?></td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                                data-bs-toggle="modal" data-bs-target="#mail-log-<?= (int) $row->id ?>"
                                                aria-label="View message details">
                                            <i class="ti ti-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php foreach ($recent as $row): ?>
                    <div class="modal modal-blur fade" id="mail-log-<?= (int) $row->id ?>"
                         tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-scrollable" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title text-truncate" title="<?= htmlspecialchars((string) $row->subject) ?>">
                                        <?= htmlspecialchars((string) $row->subject) ?>
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <dl class="row mb-0">
                                        <dt class="col-3">Recipient</dt>
                                        <dd class="col-9 text-break"><?= htmlspecialchars((string) $row->to_address) ?></dd>

                                        <dt class="col-3">From</dt>
                                        <dd class="col-9 text-break"><?= htmlspecialchars((string) ($row->from_address ?? '')) ?: '—' ?></dd>

                                        <dt class="col-3">Status</dt>
                                        <dd class="col-9">
                                            <?php if ($row->status === 'sent'): ?>
                                                <span class="badge bg-success-lt">Sent</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-lt">Failed</span>
                                            <?php endif; ?>
                                        </dd>

                                        <dt class="col-3">Transport</dt>
                                        <dd class="col-9"><?= htmlspecialchars((string) $row->transport) ?></dd>

                                        <dt class="col-3">Sent</dt>
                                        <dd class="col-9"><?= htmlspecialchars((string) $row->sent_at) ?></dd>

                                        <dt class="col-3">Error</dt>
                                        <dd class="col-9 small text-danger text-break" style="white-space: pre-wrap;">
                                            <?= htmlspecialchars((string) ($row->error ?? '')) ?: '—' ?>
                                        </dd>
                                    </dl>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="card-footer text-secondary small">
                    <?= (int) $sentCount ?> successful send<?= $sentCount === 1 ? '' : 's' ?> on record.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('click', function(e) {
    var btn = e.target.closest('[data-toggle-password]');
    if (!btn) return;
    var input = document.querySelector(btn.dataset.togglePassword);
    if (!input) return;
    var icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) icon.classList.replace('ti-eye', 'ti-eye-off');
    } else {
        input.type = 'password';
        if (icon) icon.classList.replace('ti-eye-off', 'ti-eye');
    }
});
</script>