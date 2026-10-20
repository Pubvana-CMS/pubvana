<?php
/**
 * One repeater row. Included for each saved row and once as the hidden
 * clone template.
 *
 * The row index is either the saved array key or the literal __INDEX__.
 * _repeater_script.php rewrites names, ids, and label targets on clone
 * and after a row is removed.
 *
 * @var array<string, array<string, mixed>> $repeaterFields
 * @var string                $inputPrefix e.g. 'values[items]' or 'options[group][items]'
 * @var string                $idPrefix    Base id, e.g. 'block-option-12-items'
 * @var array<string, mixed>  $row
 * @var string                $rowIndex    Saved key or __INDEX__
 * @var bool                  $isTemplate
 */
$repeaterFields = is_array($repeaterFields ?? null) ? $repeaterFields : [];
$row = is_array($row ?? null) ? $row : [];
$rowIndex = (string) ($rowIndex ?? '__INDEX__');
$isTemplate = (bool) ($isTemplate ?? false);
?>
<div class="repeater-row card card-body p-2 mb-2" data-repeater-row<?= $isTemplate ? ' data-repeater-template hidden' : '' ?>>
    <div class="d-flex gap-2 align-items-end flex-wrap">
        <?php foreach ($repeaterFields as $subKey => $subDef): ?>
            <?php
            if (!is_array($subDef)) {
                continue;
            }
            $subKey = (string) $subKey;
            $subType = (string) ($subDef['type'] ?? 'input');
            $subLabel = (string) ($subDef['label'] ?? $subKey);
            $name = $inputPrefix . '[' . $rowIndex . '][' . $subKey . ']';
            $id = $idPrefix . '-' . $rowIndex . '-' . $subKey;
            $default = $subDef['default'] ?? '';
            $value = (string) ($row[$subKey] ?? (is_scalar($default) ? $default : ''));
            ?>
            <div class="flex-fill">
                <label class="form-label small mb-1" data-subkey-label="<?= htmlspecialchars($subKey) ?>">
                    <?= htmlspecialchars($subLabel) ?>
                </label>

                <?php if ($subType === 'textarea'): ?>
                    <textarea class="form-control form-control-sm"
                              id="<?= htmlspecialchars($id) ?>"
                              name="<?= htmlspecialchars($name) ?>"
                              data-subkey="<?= htmlspecialchars($subKey) ?>"
                              data-repeater-id
                              <?= $isTemplate ? 'disabled' : '' ?>
                              rows="<?= (int) ($subDef['rows'] ?? 3) ?>"><?= htmlspecialchars($value) ?></textarea>

                <?php elseif ($subType === 'select'): ?>
                    <?php $choices = is_array($subDef['choices'] ?? null) ? $subDef['choices'] : []; ?>
                    <select class="form-select form-select-sm"
                            id="<?= htmlspecialchars($id) ?>"
                            name="<?= htmlspecialchars($name) ?>"
                            data-subkey="<?= htmlspecialchars($subKey) ?>"
                            data-repeater-id
                            <?= $isTemplate ? 'disabled' : '' ?>>
                        <?php foreach ($choices as $choiceValue => $choiceLabel): ?>
                            <option value="<?= htmlspecialchars((string) $choiceValue) ?>"
                                    <?= $value === (string) $choiceValue ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) $choiceLabel) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                <?php elseif ($subType === 'toggle'): ?>
                    <?php $checked = (bool) ($row[$subKey] ?? $subDef['default'] ?? false); ?>
                    <label class="form-check form-switch mb-0">
                        <input type="hidden"
                               name="<?= htmlspecialchars($name) ?>"
                               data-subkey="<?= htmlspecialchars($subKey) ?>"
                               <?= $isTemplate ? 'disabled' : '' ?>
                               value="0">
                        <input class="form-check-input" type="checkbox"
                               id="<?= htmlspecialchars($id) ?>"
                               name="<?= htmlspecialchars($name) ?>"
                               data-subkey="<?= htmlspecialchars($subKey) ?>"
                               data-repeater-id
                               <?= $isTemplate ? 'disabled' : '' ?>
                               value="1"
                               <?= $checked ? 'checked' : '' ?>>
                    </label>

                <?php elseif ($subType === 'color'): ?>
                    <input type="color" class="form-control form-control-color"
                           id="<?= htmlspecialchars($id) ?>"
                           name="<?= htmlspecialchars($name) ?>"
                           data-subkey="<?= htmlspecialchars($subKey) ?>"
                           data-repeater-id
                           <?= $isTemplate ? 'disabled' : '' ?>
                           value="<?= htmlspecialchars($value) ?>">

                <?php elseif ($subType === 'number'): ?>
                    <input type="number" class="form-control form-control-sm"
                           id="<?= htmlspecialchars($id) ?>"
                           name="<?= htmlspecialchars($name) ?>"
                           data-subkey="<?= htmlspecialchars($subKey) ?>"
                           data-repeater-id
                           <?= $isTemplate ? 'disabled' : '' ?>
                           value="<?= htmlspecialchars($value) ?>"
                           <?php if (isset($subDef['min'])): ?>min="<?= htmlspecialchars((string) $subDef['min']) ?>"<?php endif; ?>
                           <?php if (isset($subDef['max'])): ?>max="<?= htmlspecialchars((string) $subDef['max']) ?>"<?php endif; ?>
                           <?php if (isset($subDef['step'])): ?>step="<?= htmlspecialchars((string) $subDef['step']) ?>"<?php endif; ?>>

                <?php elseif ($subType === 'range'): ?>
                    <div class="d-flex align-items-center gap-2">
                        <input type="range" class="form-range"
                               id="<?= htmlspecialchars($id) ?>"
                               name="<?= htmlspecialchars($name) ?>"
                               data-subkey="<?= htmlspecialchars($subKey) ?>"
                               data-repeater-id
                               data-repeater-range
                               <?= $isTemplate ? 'disabled' : '' ?>
                               value="<?= htmlspecialchars($value) ?>"
                               min="<?= htmlspecialchars((string) ($subDef['min'] ?? 0)) ?>"
                               max="<?= htmlspecialchars((string) ($subDef['max'] ?? 100)) ?>"
                               step="<?= htmlspecialchars((string) ($subDef['step'] ?? 1)) ?>">
                        <output data-repeater-output><?= htmlspecialchars($value) ?></output>
                    </div>

                <?php else: ?>
                    <input type="text" class="form-control form-control-sm"
                           id="<?= htmlspecialchars($id) ?>"
                           name="<?= htmlspecialchars($name) ?>"
                           data-subkey="<?= htmlspecialchars($subKey) ?>"
                           data-repeater-id
                           <?= $isTemplate ? 'disabled' : '' ?>
                           value="<?= htmlspecialchars($value) ?>">
                <?php endif; ?>

                <?php if (!empty($subDef['help'])): ?>
                    <span class="form-hint d-block"><?= htmlspecialchars((string) $subDef['help']) ?></span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <button type="button" class="btn btn-sm btn-ghost-danger btn-icon repeater-remove" title="Remove row">
            <i class="ti ti-trash"></i>
        </button>
    </div>
</div>
