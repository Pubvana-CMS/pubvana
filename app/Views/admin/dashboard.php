<?php
/**
 * Admin Dashboard
 *
 * @var string $pageTitle
 * @var array $cards
 * @var array $sections
 */
?>

<?php
/**
 * Admin Dashboard
 *
 * Each group in $groups is ['id', 'label', 'items' => [...]]. A group item is
 * either a card (has a 'value') or a section (has a 'title' and optional
 * 'items'/'type'). Cards render as stat tiles, sections as list/action panels.
 *
 * @var string $pageTitle
 * @var array  $groups
 */
?>

<?php if (!empty($groups)): ?>
<?php foreach ($groups as $group): ?>
    <?php if (empty($group['items'])): continue; endif; ?>
    <div class="d-flex align-items-center justify-content-between mb-2 mt-3">
        <h3 class="subheader mb-0"><?= htmlspecialchars((string) $group['label']) ?></h3>
    </div>

    <?php $cards = array_filter($group['items'], fn($i) => isset($i['value']) || isset($i['label']) && !isset($i['title'])); ?>
    <?php $sections = array_filter($group['items'], fn($i) => isset($i['title'])); ?>

    <?php if (!empty($cards)): ?>
    <div class="row row-deck row-cards mb-3">
        <?php foreach ($cards as $card): ?>
            <?php $tone = htmlspecialchars($card['tone'] ?? 'primary'); ?>
            <div class="col-sm-6 col-xl-4 col-xxl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between gap-3">
                            <div>
                                <div class="subheader"><?= htmlspecialchars((string) $card['label']) ?></div>
                                <div class="h1 mb-1"><?= htmlspecialchars((string) $card['value']) ?></div>
                                <?php if (!empty($card['description'])): ?>
                                    <div class="text-secondary"><?= htmlspecialchars((string) $card['description']) ?></div>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($card['icon'])): ?>
                                <span class="avatar avatar-md bg-<?= $tone ?>-lt text-<?= $tone ?>">
                                    <i class="ti <?= htmlspecialchars((string) $card['icon']) ?>"></i>
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($card['trend']) && is_array($card['trend'])): ?>
                            <div class="mt-3 text-secondary small">
                                <span class="text-<?= htmlspecialchars((string) ($card['trend']['direction'] === 'down' ? 'danger' : ($card['trend']['direction'] === 'up' ? 'success' : 'secondary'))) ?>">
                                    <?= htmlspecialchars((string) ($card['trend']['value'] ?? '')) ?>
                                </span>
                                <?php if (!empty($card['trend']['label'])): ?>
                                    <span><?= htmlspecialchars((string) $card['trend']['label']) ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($card['href'])): ?>
                        <div class="card-footer bg-transparent">
                            <a href="<?= htmlspecialchars((string) $card['href']) ?>" class="btn btn-sm btn-outline-secondary">Open</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($sections)): ?>
    <div class="row row-deck row-cards mb-3">
        <?php foreach ($sections as $section): ?>
            <?php $tone = htmlspecialchars($section['tone'] ?? 'primary'); ?>
            <div class="<?= htmlspecialchars((string) ($section['col'] ?? 'col-12 col-xl-6')) ?>">
                <div class="card h-100">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title mb-1">
                                <?php if (!empty($section['icon'])): ?>
                                    <i class="ti <?= htmlspecialchars((string) $section['icon']) ?> me-2 text-<?= $tone ?>"></i>
                                <?php endif; ?>
                                <?= htmlspecialchars((string) $section['title']) ?>
                            </h3>
                            <?php if (!empty($section['description'])): ?>
                                <div class="text-secondary small"><?= htmlspecialchars((string) $section['description']) ?></div>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($section['href'])): ?>
                            <div class="card-actions">
                                <a href="<?= htmlspecialchars((string) $section['href']) ?>" class="btn btn-sm btn-outline-secondary">View all</a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="list-group list-group-flush">
                        <?php if (($section['type'] ?? 'list') === 'actions'): ?>
                            <?php if (empty($section['items'])): ?>
                                <div class="list-group-item text-secondary"><?= htmlspecialchars((string) ($section['empty_state'] ?? 'Nothing to show yet.')) ?></div>
                            <?php else: ?>
                                <div class="card-body">
                                    <div class="btn-list">
                                        <?php foreach ($section['items'] as $item): ?>
                                            <?php $emphasis = htmlspecialchars((string) ($item['emphasis'] ?? 'secondary')); ?>
                                            <a href="<?= htmlspecialchars((string) $item['href']) ?>" class="btn btn-outline-<?= $emphasis ?>">
                                                <?php if (!empty($item['icon'])): ?>
                                                    <i class="ti <?= htmlspecialchars((string) $item['icon']) ?> me-1"></i>
                                                <?php endif; ?>
                                                <?= htmlspecialchars((string) $item['label']) ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if (empty($section['items'])): ?>
                                <div class="list-group-item text-secondary"><?= htmlspecialchars((string) ($section['empty_state'] ?? 'Nothing to show yet.')) ?></div>
                            <?php else: ?>
                                <?php foreach ($section['items'] as $item): ?>
                                    <?php
                                        $itemHref    = (string) ($item['href'] ?? '');
                                        $sectionHref = (string) ($section['href'] ?? '');
                                        $isLink      = $itemHref !== '' && $itemHref !== $sectionHref;
                                        $itemClass   = 'list-group-item';
                                        if ($isLink) {
                                            $itemClass .= ' list-group-item-action';
                                        }
                                    ?>
                                    <<?= $isLink ? 'a href="' . htmlspecialchars($itemHref) . '"' : 'div' ?> class="<?= $itemClass ?>">
                                        <div class="d-flex align-items-center justify-content-between gap-3">
                                            <div>
                                                <div class="fw-medium"><?= htmlspecialchars((string) $item['label']) ?></div>
                                                <?php if (!empty($item['sub'])): ?>
                                                    <div class="text-secondary small"><?= htmlspecialchars((string) $item['sub']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($item['thumb'])): ?>
                                                <img src="<?= htmlspecialchars((string) $item['thumb']) ?>" alt=""
                                                     class="flex-shrink-0"
                                                     style="width:38px;height:38px;object-fit:cover;border-radius:.375rem;"
                                                     loading="lazy">
                                            <?php elseif (($item['thumb_type'] ?? '') === 'icon'): ?>
                                                <div class="d-flex align-items-center justify-content-center flex-shrink-0 text-secondary"
                                                     style="width:38px;height:38px;">
                                                    <i class="ti ti-photo text-secondary" style="font-size:1.5rem;"></i>
                                                </div>
                                            <?php elseif (!empty($item['meta'])): ?>
                                                <div class="text-secondary small"><?= htmlspecialchars((string) $item['meta']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </<?= $isLink ? 'a' : 'div' ?>>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
<?php endforeach; ?>
<?php endif; ?>

<?php if (empty($groups)): ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="ti ti-dashboard" style="font-size: 3rem; opacity: .3"></i>
        <h3 class="mt-3">Welcome to Pubvana</h3>
        <p class="text-secondary">Install some plugins to get started.</p>
    </div>
</div>
<?php endif; ?>

