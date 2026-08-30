<?php
/**
 * SEO Settings admin page.
 *
 * @var string $pageTitle
 * @var array  $settings
 * @var array  $aiCrawlers
 * @var string $defaultOgImagePicker
 * @var string $organizationLogoPicker
 */
?>

<form method="post" action="/admin/seo">
    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">

    <!-- General SEO Settings -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title"><i class="ti ti-settings me-2"></i>General</h3>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Title Separator</label>
                    <input type="text" name="title_separator" class="form-control"
                           value="<?= htmlspecialchars($settings['title_separator']) ?>"
                           placeholder="|">
                    <small class="form-hint">Character between page title and site name (e.g. | or -)</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Default Language</label>
                    <input type="text" name="default_language" class="form-control"
                           value="<?= htmlspecialchars($settings['default_language']) ?>"
                           placeholder="en">
                    <small class="form-hint">ISO language code for hreflang (e.g. en, en-US, fr)</small>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Title Template</label>
                <input type="text" name="title_template" class="form-control"
                       value="<?= htmlspecialchars($settings['title_template']) ?>"
                       placeholder="{title} {sep} {site_name}">
                <small class="form-hint">
                    <code>{title}</code> Page or post title
                    <code>{sep}</code> The separator character set above
                    <code>{site_name}</code> Your site name from general settings
                </small>
            </div>
            <div class="mb-3">
                <label class="form-label">Default OG Image</label>
                <?= $defaultOgImagePicker ?>
                <small class="form-hint">Fallback image for social shares when no content image is set. Recommended: 1200x600.</small>
            </div>
        </div>
    </div>

    <!-- Organization / Identity -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title"><i class="ti ti-building me-2"></i>Organization</h3>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Organization Name</label>
                    <input type="text" name="organization_name" class="form-control"
                           value="<?= htmlspecialchars($settings['organization_name']) ?>"
                           placeholder="Defaults to site name">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Organization Logo</label>
                    <?= $organizationLogoPicker ?>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Social Profiles</label>
                <textarea name="social_profiles" class="form-control" rows="4"
                          placeholder="https://twitter.com/yourhandle&#10;https://facebook.com/yourpage&#10;https://linkedin.com/company/yourco"><?= htmlspecialchars(is_array($settings['social_profiles']) ? implode("\n", $settings['social_profiles']) : '') ?></textarea>
                <small class="form-hint">One URL per line. Used in Organization schema sameAs property.</small>
            </div>
        </div>
    </div>

    <!-- Verification Codes -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title"><i class="ti ti-certificate me-2"></i>Verification</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Google Search Console</label>
                    <input type="text" name="verification_google" class="form-control"
                           value="<?= htmlspecialchars($settings['verification_google']) ?>"
                           placeholder="Verification code only (not the full meta tag)">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Bing Webmaster Tools</label>
                    <input type="text" name="verification_bing" class="form-control"
                           value="<?= htmlspecialchars($settings['verification_bing']) ?>"
                           placeholder="Verification code only">
                </div>
            </div>
        </div>
    </div>

    <!-- Sitemap -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title"><i class="ti ti-sitemap me-2"></i>XML Sitemap</h3>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-check">
                    <input type="checkbox" name="sitemap_enabled" class="form-check-input"
                           <?= $settings['sitemap_enabled'] ? 'checked' : '' ?>>
                    <span class="form-check-label">Enable XML Sitemap</span>
                </label>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <label class="form-check">
                        <input type="checkbox" name="sitemap_include_pages" class="form-check-input"
                               <?= $settings['sitemap_include_pages'] ? 'checked' : '' ?>>
                        <span class="form-check-label">Include Pages</span>
                    </label>
                </div>
                <div class="col-md-6">
                    <label class="form-check">
                        <input type="checkbox" name="sitemap_include_posts" class="form-check-input"
                               <?= $settings['sitemap_include_posts'] ? 'checked' : '' ?>>
                        <span class="form-check-label">Include Blog Posts</span>
                    </label>
                </div>
            </div>
            <div class="mt-3">
                <small class="form-hint">Sitemap is served at <code>/sitemap.xml</code>. Only published, indexable content is included.</small>
            </div>
        </div>
    </div>

    <!-- llms.txt -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title"><i class="ti ti-robot me-2"></i>llms.txt (AI Discovery)</h3>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-check">
                    <input type="checkbox" name="llms_txt_enabled" class="form-check-input"
                           <?= $settings['llms_txt_enabled'] ? 'checked' : '' ?>>
                    <span class="form-check-label">Enable llms.txt</span>
                </label>
                <small class="form-hint">Provides AI crawlers with a curated content map at <code>/llms.txt</code></small>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <label class="form-check">
                        <input type="checkbox" name="llms_txt_include_pages" class="form-check-input"
                               <?= $settings['llms_txt_include_pages'] ? 'checked' : '' ?>>
                        <span class="form-check-label">Include Pages</span>
                    </label>
                </div>
                <div class="col-md-6">
                    <label class="form-check">
                        <input type="checkbox" name="llms_txt_include_posts" class="form-check-input"
                               <?= $settings['llms_txt_include_posts'] ? 'checked' : '' ?>>
                        <span class="form-check-label">Include Blog Posts</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Disclosure -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title"><i class="ti ti-sparkles me-2"></i>AI Disclosure</h3>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-check">
                    <input type="checkbox" name="ai_disclosure_enabled" class="form-check-input"
                           <?= $settings['ai_disclosure_enabled'] ? 'checked' : '' ?>>
                    <span class="form-check-label">Show AI-assistance disclosure</span>
                </label>
                <small class="form-hint d-block">Shows a visible note on AI-generated posts and pages, and flags them in structured data.</small>
            </div>
        </div>
    </div>

    <!-- AI Crawler Management -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title"><i class="ti ti-shield-bolt me-2"></i>AI Crawler Access</h3>
        </div>
        <div class="card-body">
            <p class="text-secondary mb-3">Control which AI bots can access your content. <strong>Allow</strong> means the bot can read your site. <strong>Block</strong> means it cannot. Training bots are blocked by default to prevent your content from being used to train AI models.</p>
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead>
                        <tr>
                            <th>Bot</th>
                            <th>Purpose</th>
                            <th>Access</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($aiCrawlers as $bot => $info): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($bot) ?></code></td>
                                <td class="text-secondary"><?= htmlspecialchars($info['description']) ?></td>
                                <td>
                                    <select name="ai_crawlers[<?= htmlspecialchars($bot) ?>]" class="form-select form-select-sm" style="width: auto;">
                                        <option value="allow" <?= $info['current'] === 'allow' ? 'selected' : '' ?>>Allow</option>
                                        <option value="block" <?= $info['current'] === 'block' ? 'selected' : '' ?>>Block</option>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- robots.txt Custom -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title"><i class="ti ti-file-text me-2"></i>robots.txt</h3>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Custom robots.txt Content</label>
                <textarea name="robots_txt_custom" class="form-control font-monospace" rows="8"
                          placeholder="User-agent: *&#10;Allow: /&#10;Disallow: /admin/"><?= htmlspecialchars($settings['robots_txt_custom']) ?></textarea>
                <small class="form-hint">Leave empty for defaults. AI crawler directives from above are appended automatically. Sitemap URL is always appended.</small>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-primary">
            <i class="ti ti-device-floppy me-1"></i> Save Settings
        </button>
    </div>
</form>
