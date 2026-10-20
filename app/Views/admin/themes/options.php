<?php
/**
 * Theme options form, renders fields from pubvana.json provides.options.
 *
 * Supported field types: toggle, input, textarea, number, range, select,
 * color, media, font, repeater, group.
 * Groups render as a card with their fields nested inside.
 * Repeater subfields support input, textarea, select, toggle, color, number,
 * and range. Wysiwyg and media are not available inside a repeater.
 *
 * @var object $theme   Theme record
 * @var array  $options Option definitions from pubvana.json
 * @var array  $saved   Current saved values (key => value, groups use "group.field" keys)
 * @var array  $fontChoices Label => CSS font stack for font fields
 */
?>

<div class="mb-3">
    <a href="/admin/themes" class="btn btn-outline-secondary btn-sm">
        <i class="ti ti-arrow-left me-1"></i>Back to Themes
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Customize: <?= htmlspecialchars($theme->name) ?></h3>
    </div>
    <div class="card-body">
        <?php if (empty($options)): ?>
            <p class="text-secondary">This theme has no configurable options.</p>
        <?php else: ?>
        <form method="post" action="/admin/themes/<?= (int) $theme->id ?>/options">
            <?= csrf_field() ?>

            <?php foreach ($options as $key => $opt):
                $type = $opt['type'] ?? 'input';
            ?>
                <?php if ($type === 'group'): ?>
                    <div class="card mb-3">
                        <div class="card-header">
                            <h4 class="card-title mb-0"><?= htmlspecialchars($opt['label'] ?? $key) ?></h4>
                        </div>
                        <div class="card-body">
                            <?php foreach (($opt['fields'] ?? []) as $fKey => $fDef):
                                $inputName = "options[{$key}][{$fKey}]";
                                $dbKey = $key . '.' . $fKey;
                                $fieldDef = $fDef;
                                include __DIR__ . '/_option_field.php';
                            endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <?php
                        $inputName = "options[{$key}]";
                        $dbKey = $key;
                        $fieldDef = $opt;
                        include __DIR__ . '/_option_field.php';
                    ?>
                <?php endif; ?>
            <?php endforeach; ?>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Save Options</button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/_repeater_script.php'; ?>
