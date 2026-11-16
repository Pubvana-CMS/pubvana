<?php
/**
 * Full list page for top content or referrers.
 *
 * @var string     $pageTitle
 * @var string     $kind
 * @var string     $range
 * @var array      $rows
 * @var int        $page
 * @var int        $perPage
 * @var string     $adminBase
 * @var array|null $pagination
 */
$isContent = $kind === 'content';
$rangeLabels = ['7' => '7d', '30' => '30d', '90' => '90d', '180' => '180d', '365' => '1y', 'all' => 'All'];
$backUrl = $adminBase . '?range=' . urlencode($range);
$otherUrl = $adminBase . '/' . ($isContent ? 'referrers' : 'content') . '?range=' . urlencode($range);
$otherLabel = $isContent ? 'All Referrers' : 'All Content';
$numbered = ($page - 1) * $perPage;
?>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <div class="d-flex align-items-center gap-2">
        <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($otherUrl) ?>"><?= $otherLabel ?></a>
        <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($backUrl) ?>">Back to report</a>
    </div>
    <div class="btn-group btn-group-sm" role="group" aria-label="Range filter">
        <?php foreach ($rangeLabels as $value => $label): ?>
            <a class="btn btn-outline-primary <?= (string) $value === $range ? 'active' : '' ?>"
               href="<?= htmlspecialchars($adminBase . '/' . $kind . '?range=' . urlencode((string) $value)) ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
            <?php if ($isContent): ?>
                <tr>
                    <th class="w-1">#</th>
                    <th>Path</th>
                    <th>Group</th>
                    <th class="w-1 text-end">Views</th>
                </tr>
            <?php else: ?>
                <tr>
                    <th>Domain</th>
                    <th class="w-1 text-end">Views</th>
                </tr>
            <?php endif; ?>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="<?= $isContent ? 4 : 2 ?>" class="text-center text-secondary py-4">Nothing recorded in this period.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $index => $row): ?>
                    <?php if ($isContent): ?>
                        <tr>
                            <td class="text-secondary"><?= $numbered + (int) $index + 1 ?></td>
                            <td>
                                <a href="<?= htmlspecialchars($row['page_path']) ?>" target="_blank" rel="noopener">
                                    <?= htmlspecialchars($row['page_path']) ?>
                                </a>
                            </td>
                            <td><span class="badge bg-secondary-lt"><?= htmlspecialchars($row['page_group']) ?></span></td>
                            <td class="text-end fw-bold"><?= number_format((int) $row['view_count']) ?></td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td class="small"><?= htmlspecialchars($row['referrer_domain']) ?></td>
                            <td class="text-end fw-bold"><?= number_format((int) $row['view_count']) ?></td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include PROJECT_ROOT . '/app/Views/admin/_pagination.php'; ?>
