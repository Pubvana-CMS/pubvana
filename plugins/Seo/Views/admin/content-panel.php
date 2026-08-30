<?php
/**
 * SEO content panel — injected into page/post edit forms.
 *
 * Expected variables:
 * @var string $content_type        'page' or 'post'
 * @var int    $content_id          ID of the content item (0 if new)
 * @var string $content_url_base    Base URL prefix for the content type
 * @var mixed  $seo_meta            Current SEO meta values (SeoMeta or array)
 * @var string $ogImagePicker       Rendered media picker for the OG image field
 */

if ($seo_meta instanceof \Pubvana\Plugins\Seo\Models\SeoMeta) {
    $focusKeywords = $seo_meta->getFocusKeywordsArray();
    $seo_meta = $seo_meta->toArray();
} else {
    $seo_meta = $seo_meta ?? [];
    $focusKeywords = is_array($seo_meta['focus_keywords'] ?? null) ? $seo_meta['focus_keywords'] : [];
}

$focusKeywordsValue = !empty($focusKeywords) ? implode(', ', $focusKeywords) : '';
?>

<div class="card mb-4" id="seo-panel">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title"><i class="ti ti-seo me-2"></i>SEO</h3>
        <div id="seo-score-badge">
            <?php if (!empty($seo_meta['seo_score'])): ?>
                <span class="badge bg-<?= $seo_meta['seo_score'] >= 70 ? 'success-lt' : ($seo_meta['seo_score'] >= 40 ? 'warning-lt' : 'danger-lt') ?>">
                    Score: <?= (int) $seo_meta['seo_score'] ?>/100
                </span>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <!-- Search Preview -->
        <div class="mb-4 p-3 bg-light rounded" id="seo-preview">
            <small class="text-secondary d-block mb-1">Search Preview</small>
            <div id="seo-preview-title" class="text-primary" style="font-size: 1.1rem; line-height: 1.3;">
                <?= htmlspecialchars($seo_meta['meta_title'] ?? 'Page Title') ?>
            </div>
            <div id="seo-preview-url" class="text-success small">
                <?= htmlspecialchars($seo_meta['canonical_url'] ?? '') ?>
            </div>
            <div id="seo-preview-desc" class="text-secondary small mt-1">
                <?= htmlspecialchars($seo_meta['meta_description'] ?? 'Meta description will appear here...') ?>
            </div>
        </div>

        <!-- Focus Keywords -->
        <div class="mb-3">
            <label class="form-label">Focus Keywords <small class="text-secondary">(up to 5, comma-separated)</small></label>
            <input type="text" name="seo[focus_keywords]" class="form-control" id="seo-focus-keywords"
                   value="<?= htmlspecialchars($focusKeywordsValue) ?>"
                   placeholder="primary keyword, secondary keyword">
        </div>

        <!-- Meta Title -->
        <div class="mb-3">
            <label class="form-label">Meta Title</label>
            <input type="text" name="seo[meta_title]" class="form-control" id="seo-meta-title"
                   value="<?= htmlspecialchars($seo_meta['meta_title'] ?? '') ?>"
                   placeholder="Leave empty to use title template" maxlength="70">
            <div class="d-flex justify-content-between">
                <small class="form-hint">Optimal: 50–60 characters</small>
                <small class="text-secondary" id="seo-title-count">0/60</small>
            </div>
        </div>

        <!-- Meta Description -->
        <div class="mb-3">
            <label class="form-label">Meta Description</label>
            <textarea name="seo[meta_description]" class="form-control" id="seo-meta-description" rows="3"
                      placeholder="Compelling description for search results" maxlength="170"><?= htmlspecialchars($seo_meta['meta_description'] ?? '') ?></textarea>
            <div class="d-flex justify-content-between">
                <small class="form-hint">Optimal: 120–160 characters</small>
                <small class="text-secondary" id="seo-desc-count">0/160</small>
            </div>
        </div>

        <!-- Canonical URL -->
        <div class="mb-3">
            <label class="form-label">Preferred URL <small class="text-secondary">(only set this if this content also lives at a different URL)</small></label>
            <input type="text" name="seo[canonical_url]" class="form-control" id="seo-canonical"
                   value="<?= htmlspecialchars($seo_meta['canonical_url'] ?? '') ?>"
                   placeholder="Leave empty for auto-generated canonical">
        </div>

        <!-- Robots Directive -->
        <div class="mb-3">
            <label class="form-label">Robots Directive</label>
            <select name="seo[robots_directive]" class="form-select" id="seo-robots">
                <option value="" <?= empty($seo_meta['robots_directive']) ? 'selected' : '' ?>>Default (index, follow)</option>
                <option value="noindex" <?= ($seo_meta['robots_directive'] ?? '') === 'noindex' ? 'selected' : '' ?>>noindex</option>
                <option value="nofollow" <?= ($seo_meta['robots_directive'] ?? '') === 'nofollow' ? 'selected' : '' ?>>nofollow</option>
                <option value="noindex, nofollow" <?= ($seo_meta['robots_directive'] ?? '') === 'noindex, nofollow' ? 'selected' : '' ?>>noindex, nofollow</option>
            </select>
        </div>

        <!-- Open Graph / Social Overrides -->
        <div class="mb-3">
            <h4 class="mb-3">Social Sharing</h4>
            <div>
                <div class="mb-3">
                    <label class="form-label">OG Title</label>
                    <input type="text" name="seo[og_title]" class="form-control"
                           value="<?= htmlspecialchars($seo_meta['og_title'] ?? '') ?>"
                           placeholder="Defaults to meta title">
                </div>
                <div class="mb-3">
                    <label class="form-label">OG Description</label>
                    <textarea name="seo[og_description]" class="form-control" rows="2"
                              placeholder="Defaults to meta description"><?= htmlspecialchars($seo_meta['og_description'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">OG Image</label>
                    <small class="form-hint d-block mb-2">Defaults to featured image or site default</small>
                    <?= $ogImagePicker ?>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Content Type <small class="text-secondary">(how Facebook/LinkedIn display this)</small></label>
                        <select name="seo[og_type]" class="form-select">
                            <option value="" <?= empty($seo_meta['og_type']) ? 'selected' : '' ?>>Auto (article for posts, website for pages)</option>
                            <option value="article" <?= ($seo_meta['og_type'] ?? '') === 'article' ? 'selected' : '' ?>>Article</option>
                            <option value="website" <?= ($seo_meta['og_type'] ?? '') === 'website' ? 'selected' : '' ?>>Website</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">X/Twitter Layout <small class="text-secondary">(how the link preview looks)</small></label>
                        <select name="seo[twitter_card]" class="form-select">
                            <option value="" <?= empty($seo_meta['twitter_card']) ? 'selected' : '' ?>>Large image (default)</option>
                            <option value="summary" <?= ($seo_meta['twitter_card'] ?? '') === 'summary' ? 'selected' : '' ?>>Small thumbnail</option>
                            <option value="summary_large_image" <?= ($seo_meta['twitter_card'] ?? '') === 'summary_large_image' ? 'selected' : '' ?>>Large image</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Analysis Results -->
        <div id="seo-analysis" class="mt-4" style="display: none;">
            <h4 class="mb-3">Content Analysis</h4>
            <div id="seo-analysis-results"></div>
        </div>

        <div class="mt-3">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="seo-analyze-btn">
                <i class="ti ti-analyze me-1"></i> Analyze Content
            </button>
        </div>
    </div>

    <!-- Hidden fields -->
    <input type="hidden" id="seo-content-url-base" value="<?= htmlspecialchars($content_url_base ?? '') ?>">
    <input type="hidden" id="seo-content-type" value="<?= htmlspecialchars($content_type ?? '') ?>">
    <input type="hidden" id="seo-content-id" value="<?= (int) ($content_id ?? 0) ?>">
    <input type="hidden" name="seo[seo_score]" id="seo-score-hidden" value="<?= (int) ($seo_meta['seo_score'] ?? 0) ?>">
</div>

<script>
(function () {
    'use strict';

    const titleInput = document.getElementById('seo-meta-title');
    const descInput = document.getElementById('seo-meta-description');
    const titleCount = document.getElementById('seo-title-count');
    const descCount = document.getElementById('seo-desc-count');
    const previewTitle = document.getElementById('seo-preview-title');
    const previewDesc = document.getElementById('seo-preview-desc');
    const previewUrl = document.getElementById('seo-preview-url');
    const analyzeBtn = document.getElementById('seo-analyze-btn');
    const analysisContainer = document.getElementById('seo-analysis');
    const analysisResults = document.getElementById('seo-analysis-results');
    const scoreBadge = document.getElementById('seo-score-badge');
    const scoreHidden = document.getElementById('seo-score-hidden');

    if (!titleInput || !descInput) return;

    function updateTitleCount() {
        const len = titleInput.value.length;
        titleCount.textContent = len + '/60';
        titleCount.className = 'text-secondary ' + (len >= 50 && len <= 60 ? 'text-success' :
            (len > 0 && len < 50) || len > 60 ? 'text-warning' : 'text-secondary');
    }

    function updateDescCount() {
        const len = descInput.value.length;
        descCount.textContent = len + '/160';
        descCount.className = 'text-secondary ' + (len >= 120 && len <= 160 ? 'text-success' :
            (len > 0 && len < 120) || len > 160 ? 'text-warning' : 'text-secondary');
    }

    titleInput.addEventListener('input', function () {
        updateTitleCount();
        updatePreview();
    });

    descInput.addEventListener('input', function () {
        updateDescCount();
        updatePreview();
    });

    function updatePreview() {
        const pageTitle = document.querySelector('input[name="title"]');
        const title = titleInput.value || (pageTitle ? pageTitle.value : 'Page Title');
        previewTitle.textContent = title.substring(0, 60);

        const desc = descInput.value || 'Meta description will appear here...';
        previewDesc.textContent = desc.substring(0, 160);

        const slugInput = document.querySelector('input[name="slug"]');
        const urlBase = document.getElementById('seo-content-url-base');
        if (slugInput && previewUrl && urlBase) {
            previewUrl.textContent = urlBase.value + '/' + (slugInput.value || 'page-slug');
        }
    }

    const pageTitle = document.querySelector('input[name="title"]');
    if (pageTitle) {
        pageTitle.addEventListener('input', updatePreview);
    }

    const slugInput = document.querySelector('input[name="slug"]');
    if (slugInput) {
        slugInput.addEventListener('input', updatePreview);
    }

    if (analyzeBtn) {
        analyzeBtn.addEventListener('click', runAnalysis);
    }

    function runAnalysis() {
        analyzeBtn.disabled = true;
        analyzeBtn.innerHTML = '<i class="ti ti-loader me-1 ti-spin"></i> Analyzing...';

        const contentEl = document.querySelector('textarea[name="content"]') ||
            document.querySelector('.ql-editor') ||
            document.querySelector('[name="content"]');

        let content = '';
        if (contentEl) {
            content = contentEl.value || contentEl.innerHTML || '';
        }

        const focusKeywords = document.getElementById('seo-focus-keywords');
        const slug = document.querySelector('input[name="slug"]');

        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = content;
        const images = tempDiv.querySelectorAll('img');
        const imageAlts = Array.from(images).map(img => img.getAttribute('alt') || '');

        const params = new URLSearchParams({
            title: (pageTitle ? pageTitle.value : ''),
            content: content,
            meta_title: titleInput.value,
            meta_description: descInput.value,
            focus_keywords: focusKeywords ? focusKeywords.value : '',
            slug: slug ? slug.value : '',
            has_images: images.length > 0 ? '1' : '0',
        });

        imageAlts.forEach((alt, i) => {
            params.append('image_alts[' + i + ']', alt);
        });

        fetch('/admin/seo/analyze?' + params.toString(), {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(response => response.json())
            .then(data => {
                displayResults(data);
                analyzeBtn.disabled = false;
                analyzeBtn.innerHTML = '<i class="ti ti-analyze me-1"></i> Analyze Content';
            })
            .catch(err => {
                console.error('SEO analysis error:', err);
                analyzeBtn.disabled = false;
                analyzeBtn.innerHTML = '<i class="ti ti-analyze me-1"></i> Analyze Content';
            });
    }

    function displayResults(data) {
        analysisContainer.style.display = 'block';

        const score = data.score || 0;
        scoreHidden.value = score;

        const scoreTone = score >= 70 ? 'success-lt' : (score >= 40 ? 'warning-lt' : 'danger-lt');
        scoreBadge.innerHTML = '<span class="badge bg-' + scoreTone + '">Score: ' + score + '/100</span>';

        let html = '<div class="list-group list-group-flush">';
        const statusIcons = {
            pass: '<i class="ti ti-circle-check text-success me-2"></i>',
            warning: '<i class="ti ti-alert-triangle text-warning me-2"></i>',
            fail: '<i class="ti ti-circle-x text-danger me-2"></i>'
        };

        const order = { fail: 0, warning: 1, pass: 2 };
        data.checks.sort((a, b) => (order[a.status] || 2) - (order[b.status] || 2));

        data.checks.forEach(function (check) {
            html += '<div class="list-group-item px-0 py-2 border-0">';
            html += (statusIcons[check.status] || '') + '<span>' + escapeHtml(check.message) + '</span>';
            html += '</div>';
        });

        html += '</div>';
        analysisResults.innerHTML = html;
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    const contentType = document.getElementById('seo-content-type');
    const contentId = document.getElementById('seo-content-id');

    function autoSave(field, value) {
        if (!contentType || !contentId || contentId.value === '0') return;

        const formData = new FormData();
        formData.append('content_type', contentType.value);
        formData.append('content_id', contentId.value);
        formData.append(field, value);
        formData.append('_csrf_token', document.querySelector('input[name="_csrf_token"]')?.value || '');

        fetch('/admin/seo/meta', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        }).catch(function (err) {
            console.error('SEO auto-save error:', err);
        });
    }

    const textFields = [
        { el: titleInput, field: 'meta_title' },
        { el: descInput, field: 'meta_description' },
        { el: document.getElementById('seo-focus-keywords'), field: 'focus_keywords' },
        { el: document.getElementById('seo-canonical'), field: 'canonical_url' },
        { el: document.querySelector('input[name="seo[og_title]"]'), field: 'og_title' },
        { el: document.querySelector('textarea[name="seo[og_description]"]'), field: 'og_description' },
        { el: document.querySelector('input[name="seo[og_image]"]'), field: 'og_image' },
    ];

    textFields.forEach(function (item) {
        if (item.el) {
            item.el.addEventListener('blur', function () {
                autoSave(item.field, this.value);
            });
        }
    });

    const selectFields = [
        { el: document.getElementById('seo-robots'), field: 'robots_directive' },
        { el: document.querySelector('select[name="seo[og_type]"]'), field: 'og_type' },
        { el: document.querySelector('select[name="seo[twitter_card]"]'), field: 'twitter_card' },
    ];

    selectFields.forEach(function (item) {
        if (item.el) {
            item.el.addEventListener('change', function () {
                autoSave(item.field, this.value);
            });
        }
    });

    updateTitleCount();
    updateDescCount();
    updatePreview();
})();
</script>
