<?php
/**
 * Analytics report.
 *
 * @var string $pageTitle
 * @var string $range
 * @var array  $report  See AnalyticsService::dashboard()
 * @var string $adminBase
 */
?>
<?php
$totalViews = (int) ($report['totalViews'] ?? 0);
$previousViews = $report['previousTotalViews'] ?? null;
$topContent = $report['topContent'] ?? [];
$referrers  = $report['referrers'] ?? [];

$viewsChange = null;
if ($previousViews !== null && (int) $previousViews > 0) {
    $viewsChange = (($totalViews - (int) $previousViews) / (int) $previousViews) * 100;
}
?>

<div class="d-flex align-items-center justify-content-end flex-wrap gap-3 mb-4">
    <div class="btn-group btn-group-sm" role="group" aria-label="Range filter" id="rangeFilter">
        <button type="button" class="btn btn-outline-primary <?= $range === '7' ? 'active' : '' ?>" data-range="7">7d</button>
        <button type="button" class="btn btn-outline-primary <?= $range === '30' ? 'active' : '' ?>" data-range="30">30d</button>
        <button type="button" class="btn btn-outline-primary <?= $range === '90' ? 'active' : '' ?>" data-range="90">90d</button>
        <button type="button" class="btn btn-outline-primary <?= $range === '180' ? 'active' : '' ?>" data-range="180">180d</button>
        <button type="button" class="btn btn-outline-primary <?= $range === '365' ? 'active' : '' ?>" data-range="365">1y</button>
        <button type="button" class="btn btn-outline-primary <?= $range === 'all' ? 'active' : '' ?>" data-range="all">All</button>
    </div>
</div>

<!-- Total views stat card -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="subheader">Total Views</div>
                <div class="h1 mb-0" id="totalViews"><?= number_format($totalViews) ?></div>
                <div class="text-secondary small" id="viewsChange">
                    <?php if ($viewsChange === null): ?>
                        No earlier period to compare
                    <?php else: ?>
                        <?= $viewsChange >= 0 ? '+' : '' ?><?= number_format($viewsChange, 1) ?>% vs previous period
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Views chart -->
<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Views</h3>
    </div>
    <div class="card-body">
        <div id="chartEmpty" class="text-center text-secondary py-4" style="display:none;">
            No views recorded in this period yet.
        </div>
        <div style="position: relative; height: 320px;">
            <canvas id="viewsChart"></canvas>
        </div>
    </div>
</div>

<div class="row">

    <!-- Top content -->
    <div class="col-lg-7">
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="card-title mb-0">Top Content</h3>
                <a class="btn btn-sm btn-outline-secondary" id="contentViewAll"
                   data-list-base="<?= htmlspecialchars($adminBase . '/content') ?>"
                   href="<?= htmlspecialchars($adminBase . '/content?range=' . urlencode($range)) ?>">View all</a>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table" id="topContentTable">
                    <thead>
                        <tr>
                            <th class="w-1">#</th>
                            <th>Path</th>
                            <th>Group</th>
                            <th class="w-1 text-end">Views</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($topContent)): ?>
                        <tr><td colspan="4" class="text-center text-secondary py-4">No views recorded in this period yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($topContent as $index => $row): ?>
                        <tr>
                            <td class="text-secondary"><?= (int) $index + 1 ?></td>
                            <td>
                                <a href="<?= htmlspecialchars($row['page_path']) ?>" target="_blank" rel="noopener">
                                    <?= htmlspecialchars($row['page_path']) ?>
                                </a>
                            </td>
                            <td>
                                <span class="badge bg-secondary-lt"><?= htmlspecialchars($row['page_group']) ?></span>
                            </td>
                            <td class="text-end fw-bold"><?= number_format((int) $row['view_count']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Top referrers -->
    <div class="col-lg-5">
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="card-title mb-0">Top Referrers</h3>
                <a class="btn btn-sm btn-outline-secondary" id="referrersViewAll"
                   data-list-base="<?= htmlspecialchars($adminBase . '/referrers') ?>"
                   href="<?= htmlspecialchars($adminBase . '/referrers?range=' . urlencode($range)) ?>">View all</a>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table" id="referrersTable">
                    <thead>
                        <tr>
                            <th>Domain</th>
                            <th class="w-1 text-end">Views</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($referrers)): ?>
                        <tr><td colspan="2" class="text-center text-secondary py-4">No referrer data in this period.</td></tr>
                    <?php else: ?>
                        <?php foreach ($referrers as $row): ?>
                        <tr>
                            <td class="small"><?= htmlspecialchars($row['referrer_domain']) ?></td>
                            <td class="text-end fw-bold"><?= number_format((int) $row['view_count']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
window._analyticsEndpoint = '<?= $adminBase ?>/data';
window._analyticsRange = <?= json_encode($range, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS) ?>;
window._analyticsReport = <?= json_encode($report, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS) ?>;

var chart = null;
var currentRange = window._analyticsRange;

var palette = [
    'rgba(32, 107, 196, 1)',
    'rgba(47, 179, 146, 1)',
    'rgba(245, 158, 11, 1)',
    'rgba(214, 66, 66, 1)',
    'rgba(124, 58, 237, 1)',
    'rgba(14, 165, 233, 1)',
    'rgba(236, 72, 153, 1)',
    'rgba(22, 163, 74, 1)',
    'rgba(202, 138, 4, 1)',
    'rgba(100, 116, 139, 1)',
    'rgba(168, 85, 247, 1)',
    'rgba(20, 184, 166, 1)',
    'rgba(234, 88, 12, 1)',
    'rgba(190, 24, 93, 1)',
    'rgba(101, 163, 13, 1)',
    'rgba(2, 132, 199, 1)',
    'rgba(217, 119, 6, 1)',
    'rgba(13, 148, 136, 1)',
    'rgba(79, 70, 229, 1)',
    'rgba(5, 150, 105, 1)'
];

// Golden-ratio hue step keeps generated colours maximally separated.
var GOLDEN_ANGLE = 0.6180339887498949;

// Colours are assigned per series label and remembered for the life of the
// page, so a group keeps the same colour when the range filter re-renders
// the chart and re-sorts the series.
var colorRegistry = {};
var usedColors = {};
var generatedCount = 0;

function hashLabel(label) {
    var hash = 0;
    for (var i = 0; i < label.length; i++) {
        hash = ((hash << 5) - hash + label.charCodeAt(i)) | 0;
    }
    return Math.abs(hash);
}

function generatedColor(step) {
    var hue = (step * GOLDEN_ANGLE % 1) * 360;
    var lightness = 42 + (Math.floor(step / 8) % 3) * 9;

    return 'hsla(' + hue.toFixed(1) + ', 70%, ' + lightness + '%, 1)';
}

// Every series gets its own colour. The label hash picks a starting point in
// the palette, then probing skips colours already taken by another series;
// past the palette the colour is generated instead of wrapping.
function colorForLabel(rawLabel) {
    var label = String(rawLabel);
    if (Object.prototype.hasOwnProperty.call(colorRegistry, label)) {
        return colorRegistry[label];
    }

    var color = null;
    var start = hashLabel(label) % palette.length;

    for (var i = 0; i < palette.length; i++) {
        var candidate = palette[(start + i) % palette.length];
        if (!usedColors[candidate]) {
            color = candidate;
            break;
        }
    }

    if (color === null) {
        do {
            color = generatedColor(generatedCount++);
        } while (usedColors[color]);
    }

    usedColors[color] = true;
    colorRegistry[label] = color;

    return color;
}

function fillColor(color) {
    return color.replace(', 1)', ', 0.12)');
}

function initChart(data) {
    var ctx = document.getElementById('viewsChart');
    if (!ctx) return;
    if (chart) { chart.destroy(); }

    var emptyBox = document.getElementById('chartEmpty');
    var series = data.series || [];
    var hasData = series.length > 0;

    emptyBox.style.display = hasData ? 'none' : 'block';

    var datasets = series.map(function(entry) {
        var color = colorForLabel(entry.label);
        return {
            label: entry.label,
            data: entry.values,
            fill: true,
            backgroundColor: fillColor(color),
            borderColor: color,
            tension: 0.3,
            pointRadius: 2,
            pointHoverRadius: 4,
            borderWidth: 2
        };
    });

    chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels || [],
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: true, position: 'bottom' }
            },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } }
            }
        }
    });
}

function renderTopContent(rows) {
    var tbody = document.querySelector('#topContentTable tbody');
    if (!tbody) return;

    if (!rows || rows.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-secondary py-4">No views recorded in this period yet.</td></tr>';
        return;
    }

    tbody.innerHTML = rows.map(function(row, i) {
        return '<tr>'
            + '<td class="text-secondary">' + (i + 1) + '</td>'
            + '<td><a href="' + escAttr(row.page_path) + '" target="_blank" rel="noopener">' + escHtml(row.page_path) + '</a></td>'
            + '<td><span class="badge bg-secondary-lt">' + escHtml(row.page_group) + '</span></td>'
            + '<td class="text-end fw-bold">' + Number(row.view_count).toLocaleString() + '</td>'
            + '</tr>';
    }).join('');
}

function renderReferrers(rows) {
    var tbody = document.querySelector('#referrersTable tbody');
    if (!tbody) return;

    if (!rows || rows.length === 0) {
        tbody.innerHTML = '<tr><td colspan="2" class="text-center text-secondary py-4">No referrer data in this period.</td></tr>';
        return;
    }

    tbody.innerHTML = rows.map(function(row) {
        return '<tr>'
            + '<td class="small">' + escHtml(row.referrer_domain) + '</td>'
            + '<td class="text-end fw-bold">' + Number(row.view_count).toLocaleString() + '</td>'
            + '</tr>';
    }).join('');
}

function renderChange(previous, current) {
    var el = document.getElementById('viewsChange');
    if (!el) return;

    if (previous === null || previous === undefined || Number(previous) === 0) {
        el.textContent = 'No earlier period to compare';
        return;
    }

    var pct = ((Number(current) - Number(previous)) / Number(previous)) * 100;
    el.textContent = (pct >= 0 ? '+' : '') + pct.toFixed(1) + '% vs previous period';
}

function fetchData(range) {
    document.querySelectorAll('[data-list-base]').forEach(function(link) {
        link.href = link.dataset.listBase + '?range=' + encodeURIComponent(range);
    });

    fetch(window._analyticsEndpoint + '?range=' + encodeURIComponent(range))
        .then(function(r) { return r.json(); })
        .then(function(data) {
            document.getElementById('totalViews').textContent = Number(data.totalViews || 0).toLocaleString();
            renderChange(data.previousTotalViews, data.totalViews);
            initChart(data.trends || { labels: [], series: [] });
            renderTopContent(data.topContent || []);
            renderReferrers(data.referrers || []);
        })
        .catch(function() {});
}

function escHtml(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function escAttr(str) {
    return escHtml(str);
}

document.addEventListener('DOMContentLoaded', function() {
    initChart(window._analyticsReport.trends || { labels: [], series: [] });

    document.getElementById('rangeFilter').addEventListener('click', function(e) {
        var btn = e.target.closest('[data-range]');
        if (!btn) return;
        document.querySelectorAll('#rangeFilter .btn').forEach(function(b) { b.classList.remove('active'); });
        btn.classList.add('active');
        currentRange = btn.dataset.range;
        fetchData(currentRange);
    });
});
</script>