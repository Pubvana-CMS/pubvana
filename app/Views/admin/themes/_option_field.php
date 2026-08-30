<?php
/**
 * Single option field partial — included from options.php.
 *
 * @var string $inputName HTML input name attribute
 * @var string $dbKey     Key used in $saved lookup
 * @var array  $fieldDef  Field definition from pubvana.json
 * @var array  $saved     All saved values
 */

$savedVal  = $saved[$dbKey] ?? ($fieldDef['default'] ?? '');
$label     = htmlspecialchars($fieldDef['label'] ?? $dbKey);
$fieldType = $fieldDef['type'] ?? 'input';
?>
<div class="mb-3 row">
    <label class="col-sm-3 col-form-label"><?= $label ?></label>
    <div class="col-sm-9">
        <?php if ($fieldType === 'toggle'): ?>
            <input type="hidden" name="<?= $inputName ?>" value="0">
            <label class="form-check form-switch">
                <input class="form-check-input" type="checkbox"
                       name="<?= $inputName ?>" value="1"
                       <?= $savedVal ? 'checked' : '' ?>>
            </label>

        <?php elseif ($fieldType === 'select'): ?>
            <select class="form-select" name="<?= $inputName ?>">
                <?php foreach (($fieldDef['choices'] ?? []) as $val => $lbl): ?>
                    <option value="<?= htmlspecialchars((string) $val) ?>" <?= $savedVal == $val ? 'selected' : '' ?>><?= htmlspecialchars($lbl) ?></option>
                <?php endforeach; ?>
            </select>

        <?php elseif ($fieldType === 'color'): ?>
            <input type="color" class="form-control form-control-color" name="<?= $inputName ?>" value="<?= htmlspecialchars($savedVal) ?>">

        <?php elseif ($fieldType === 'media'): ?>
            <?= $mediaPickers[$dbKey] ?? '' ?>

        <?php else: ?>
            <input type="text" class="form-control" name="<?= $inputName ?>"
                   value="<?= htmlspecialchars($savedVal) ?>">
        <?php endif; ?>

        <?php if (!empty($fieldDef['help'])): ?>
            <span class="form-hint"><?= htmlspecialchars($fieldDef['help']) ?></span>
        <?php endif; ?>
    </div>
</div>
