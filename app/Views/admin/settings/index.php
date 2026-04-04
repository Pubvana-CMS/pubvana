<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?= lang('Admin.settingsTitle') ?></h1>
</div>

<ul class="nav nav-tabs mb-3" id="settingsTabs" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#general"><?= lang('Admin.settingsGeneral') ?></a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#seo"><?= lang('Admin.settingsSeo') ?></a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#email"><?= lang('Admin.settingsEmail') ?></a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#social"><?= lang('Admin.settingsSocialLogin') ?></a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#sharing"><?= lang('Admin.settingsSocialSharing') ?></a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#spam"><?= lang('Admin.settingsSpam') ?></a></li>
</ul>

<div class="tab-content">

    <!-- General -->
    <div class="tab-pane fade show active" id="general">
        <div class="card shadow mb-4">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><?= lang('Admin.generalSettingsHeading') ?></h6></div>
            <div class="card-body">
                <form method="POST" action="<?= base_url('admin/settings/general') ?>">
                    <?= csrf_field() ?>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold"><?= lang('Admin.generalSiteName') ?></label>
                        <div class="col-sm-9"><input type="text" name="site_name" class="form-control" value="<?= esc(setting('App.siteName')) ?>"></div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold"><?= lang('Admin.generalTagline') ?></label>
                        <div class="col-sm-9"><input type="text" name="site_tagline" class="form-control" value="<?= esc(setting('App.siteTagline')) ?>"></div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold"><?= lang('Admin.generalAdminEmail') ?></label>
                        <div class="col-sm-9"><input type="email" name="site_email" class="form-control" value="<?= esc(setting('App.siteEmail')) ?>"></div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold"><?= lang('Admin.generalPostsPerPage') ?></label>
                        <div class="col-sm-3"><input type="number" name="posts_per_page" class="form-control" min="1" max="100" value="<?= esc(setting('App.postsPerPage') ?? 10) ?>"></div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold"><?= lang('Admin.generalComments') ?></label>
                        <div class="col-sm-9">
                            <div class="custom-control custom-switch mb-2">
                                <input type="hidden" name="comments_enabled" value="0">
                                <input type="checkbox" class="custom-control-input" id="comments_enabled" name="comments_enabled" value="1"
                                       <?= setting('App.commentsEnabled') ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="comments_enabled"><?= lang('Admin.generalCommentsEnable') ?></label>
                            </div>
                            <div class="custom-control custom-switch">
                                <input type="hidden" name="comment_moderation" value="0">
                                <input type="checkbox" class="custom-control-input" id="comment_moderation" name="comment_moderation" value="1"
                                       <?= setting('App.commentModeration') ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="comment_moderation"><?= lang('Admin.generalCommentModeration') ?></label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold"><?= lang('Admin.generalMaintenanceMode') ?></label>
                        <div class="col-sm-9">
                            <div class="custom-control custom-switch">
                                <input type="hidden" name="maintenance_mode" value="0">
                                <input type="checkbox" class="custom-control-input" id="maintenance_mode" name="maintenance_mode" value="1"
                                       <?= setting('App.maintenanceMode') ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="maintenance_mode">
                                    <?= lang('Admin.generalMaintenanceEnable') ?>
                                    <small class="text-muted d-block"><?= lang('Admin.generalMaintenanceHelp') ?></small>
                                </label>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold"><?= lang('Admin.generalFrontPage') ?></label>
                        <div class="col-sm-9">
                            <?php $fpType = setting('App.frontPageType') ?? 'blog'; ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="front_page_type" id="fp_blog" value="blog"
                                       <?= $fpType === 'blog' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="fp_blog"><?= lang('Admin.generalFrontPageBlog') ?></label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="front_page_type" id="fp_page" value="page"
                                       <?= $fpType === 'page' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="fp_page"><?= lang('Admin.generalFrontPageStatic') ?></label>
                            </div>
                            <div class="mt-2 ml-4 mb-3">
                                <select name="front_page_id" class="form-control w-50" id="front_page_id">
                                    <option value=""><?= lang('Admin.generalSelectPage') ?></option>
                                    <?php foreach ($pages as $p): ?>
                                    <option value="<?= $p->id ?>" <?= setting('App.frontPageId') == $p->id ? 'selected' : '' ?>>
                                        <?= esc($p->title) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php $hasPluginRoutes = ! empty($pluginRoutes); ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="front_page_type" id="fp_plugin" value="plugin_route"
                                       <?= $fpType === 'plugin_route' ? 'checked' : '' ?>
                                       <?= ! $hasPluginRoutes ? 'disabled' : '' ?>>
                                <label class="form-check-label<?= ! $hasPluginRoutes ? ' text-muted' : '' ?>" for="fp_plugin">
                                    <?= lang('Admin.generalFrontPagePlugin') ?>
                                    <?php if (! $hasPluginRoutes): ?>
                                        <small class="text-muted">(<?= lang('Admin.generalFrontPageNoPlugins') ?>)</small>
                                    <?php endif; ?>
                                </label>
                            </div>
                            <?php if ($hasPluginRoutes): ?>
                            <div class="mt-2 ml-4">
                                <select name="front_page_route" class="form-control w-50" id="front_page_route">
                                    <option value=""><?= lang('Admin.generalSelectRoute') ?></option>
                                    <?php foreach ($pluginRoutes as $route): ?>
                                    <option value="<?= esc($route['url']) ?>" <?= setting('App.frontPageRoute') === $route['url'] ? 'selected' : '' ?>>
                                        <?= esc($route['label']) ?> (<?= esc($route['url']) ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold"><?= lang('Admin.generalPageCacheTtl') ?></label>
                        <div class="col-sm-3">
                            <input type="number" name="page_cache_ttl" class="form-control" min="0" value="<?= esc(setting('App.pageCacheTtl') ?? 120) ?>">
                            <small class="text-muted">Seconds. 0 = disabled.</small>
                        </div>
                    </div>
                    <div class="text-right">
                        <button type="submit" class="btn btn-primary"><?= lang('Admin.generalSaveBtn') ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- SEO -->
    <div class="tab-pane fade" id="seo">
        <div class="card shadow mb-4">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><?= lang('Admin.seoSettingsHeading') ?></h6></div>
            <div class="card-body">
                <form method="POST" action="<?= base_url('admin/settings/seo') ?>">
                    <?= csrf_field() ?>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold"><?= lang('Admin.seoMetaDescription') ?></label>
                        <div class="col-sm-9">
                            <textarea name="meta_description" class="form-control" rows="3"><?= esc(setting('Seo.metaDescription')) ?></textarea>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold"><?= lang('Admin.seoGoogleAnalytics') ?></label>
                        <div class="col-sm-9"><input type="text" name="google_analytics" class="form-control" placeholder="<?= lang('Admin.seoGoogleAnalyticsPlaceholder') ?>" value="<?= esc(setting('Seo.googleAnalytics')) ?>"></div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold"><?= lang('Admin.seoSitemap') ?></label>
                        <div class="col-sm-9">
                            <div class="custom-control custom-switch mb-2">
                                <input type="hidden" name="sitemap_enabled" value="0">
                                <input type="checkbox" class="custom-control-input" id="sitemap_enabled" name="sitemap_enabled" value="1"
                                       <?= setting('Seo.sitemapEnabled') ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="sitemap_enabled">
                                    <?= lang('Admin.seoSitemapEnable') ?>
                                    <small class="text-muted d-block"><?= lang('Admin.seoSitemapHelp') ?></small>
                                </label>
                            </div>
                            <div class="custom-control custom-switch">
                                <input type="hidden" name="news_sitemap_enabled" value="0">
                                <input type="checkbox" class="custom-control-input" id="news_sitemap_enabled" name="news_sitemap_enabled" value="1"
                                       <?= setting('Seo.newsSitemapEnabled') ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="news_sitemap_enabled">
                                    <?= lang('Admin.seoNewsSitemap') ?>
                                    <small class="text-muted d-block"><?= lang('Admin.seoNewsSitemapHelp') ?></small>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <button type="submit" class="btn btn-primary"><?= lang('Admin.seoSaveBtn') ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Email -->
    <div class="tab-pane fade" id="email">
        <div class="card shadow mb-4">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><?= lang('Admin.emailSettingsHeading') ?></h6></div>
            <div class="card-body">
                <form method="POST" action="<?= base_url('admin/settings/email') ?>">
                    <?= csrf_field() ?>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold"><?= lang('Admin.emailFromName') ?></label>
                        <div class="col-sm-9"><input type="text" name="email_from_name" class="form-control" value="<?= esc(setting('Email.fromName')) ?>"></div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold"><?= lang('Admin.emailFromAddress') ?></label>
                        <div class="col-sm-9"><input type="email" name="email_from_address" class="form-control" value="<?= esc(setting('Email.fromAddress')) ?>"></div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold"><?= lang('Admin.emailProtocol') ?></label>
                        <div class="col-sm-4">
                            <select name="email_protocol" class="form-control">
                                <?php foreach (['mail' => lang('Admin.emailProtocolMail'), 'smtp' => lang('Admin.emailProtocolSmtp'), 'sendmail' => lang('Admin.emailProtocolSendmail')] as $val => $lbl): ?>
                                <option value="<?= $val ?>" <?= setting('Email.protocol') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div id="smtp-fields">
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label font-weight-bold"><?= lang('Admin.emailSmtpHost') ?></label>
                            <div class="col-sm-9"><input type="text" name="smtp_host" class="form-control" value="<?= esc(setting('Email.SMTPHost')) ?>"></div>
                        </div>
                        <div class="form-row">
                            <div class="col-md-3 offset-md-3 form-group">
                                <label><?= lang('Admin.emailSmtpPort') ?></label>
                                <input type="number" name="smtp_port" class="form-control" value="<?= esc(setting('Email.SMTPPort') ?? 587) ?>">
                            </div>
                            <div class="col-md-3 form-group">
                                <label><?= lang('Admin.emailSmtpEncryption') ?></label>
                                <select name="smtp_crypto" class="form-control">
                                    <option value=""><?= lang('Admin.emailSmtpEncryptionNone') ?></option>
                                    <option value="tls" <?= setting('Email.SMTPCrypto') === 'tls' ? 'selected' : '' ?>>TLS</option>
                                    <option value="ssl" <?= setting('Email.SMTPCrypto') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label font-weight-bold"><?= lang('Admin.emailSmtpUsername') ?></label>
                            <div class="col-sm-9"><input type="text" name="smtp_user" class="form-control" value="<?= esc(setting('Email.SMTPUser')) ?>"></div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label font-weight-bold"><?= lang('Admin.emailSmtpPassword') ?></label>
                            <div class="col-sm-9"><input type="password" name="smtp_pass" class="form-control" autocomplete="new-password"></div>
                        </div>
                    </div>
                    <div class="text-right">
                        <button type="submit" class="btn btn-primary"><?= lang('Admin.emailSaveBtn') ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- Social Login -->
    <div class="tab-pane fade" id="social">
        <div class="card shadow mb-4">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><?= lang('Admin.socialLoginHeading') ?></h6></div>
            <div class="card-body">
                <p class="text-muted small mb-3"><?= lang('Admin.socialLoginHelp') ?></p>
                <form method="POST" action="<?= base_url('admin/settings/social') ?>">
                    <?= csrf_field() ?>

                    <h6 class="font-weight-bold"><i class="fab fa-google text-danger"></i> Google</h6>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label"><?= lang('Admin.socialLoginGoogleId') ?></label>
                        <div class="col-sm-9"><input type="text" name="google_client_id" class="form-control"
                            value="<?= esc(setting('Social.googleClientId')) ?>"></div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label"><?= lang('Admin.socialLoginGoogleSecret') ?></label>
                        <div class="col-sm-9"><input type="password" name="google_client_secret" class="form-control"
                            autocomplete="new-password" placeholder="<?= lang('Admin.socialLoginPlaceholderSecret') ?>"></div>
                    </div>

                    <hr>
                    <h6 class="font-weight-bold"><i class="fab fa-facebook text-primary"></i> Facebook</h6>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label"><?= lang('Admin.socialLoginFbAppId') ?></label>
                        <div class="col-sm-9"><input type="text" name="facebook_client_id" class="form-control"
                            value="<?= esc(setting('Social.facebookClientId')) ?>"></div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label"><?= lang('Admin.socialLoginFbAppSecret') ?></label>
                        <div class="col-sm-9"><input type="password" name="facebook_client_secret" class="form-control"
                            autocomplete="new-password" placeholder="<?= lang('Admin.socialLoginPlaceholderSecret') ?>"></div>
                    </div>

                    <div class="text-right">
                        <button type="submit" class="btn btn-primary"><?= lang('Admin.socialLoginSaveBtn') ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Social Sharing -->
    <div class="tab-pane fade" id="sharing">
        <div class="card shadow mb-4">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><?= lang('Admin.socialSharingHeading') ?></h6></div>
            <div class="card-body">
                <p class="text-muted small mb-3"><?= lang('Admin.socialSharingHelp') ?></p>
                <form method="POST" action="<?= base_url('admin/settings/sharing') ?>">
                    <?= csrf_field() ?>

                    <h6 class="font-weight-bold"><i class="fab fa-twitter text-info"></i> <?= lang('Admin.socialSharingTwitter') ?></h6>
                    <p class="text-muted small"><?= lang('Admin.socialSharingTwitterHelp') ?></p>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label"><?= lang('Admin.socialSharingApiKey') ?></label>
                        <div class="col-sm-9"><input type="password" name="twitter_api_key" class="form-control"
                            autocomplete="new-password" placeholder="<?= lang('Admin.socialLoginPlaceholderSecret') ?>"></div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label"><?= lang('Admin.socialSharingApiSecret') ?></label>
                        <div class="col-sm-9"><input type="password" name="twitter_api_secret" class="form-control"
                            autocomplete="new-password" placeholder="<?= lang('Admin.socialLoginPlaceholderSecret') ?>"></div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label"><?= lang('Admin.socialSharingAccessToken') ?></label>
                        <div class="col-sm-9"><input type="password" name="twitter_access_token" class="form-control"
                            autocomplete="new-password" placeholder="<?= lang('Admin.socialLoginPlaceholderSecret') ?>"></div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label"><?= lang('Admin.socialSharingAccessSecret') ?></label>
                        <div class="col-sm-9"><input type="password" name="twitter_access_secret" class="form-control"
                            autocomplete="new-password" placeholder="<?= lang('Admin.socialLoginPlaceholderSecret') ?>"></div>
                    </div>

                    <hr>
                    <h6 class="font-weight-bold"><i class="fab fa-facebook text-primary"></i> <?= lang('Admin.socialSharingFbPage') ?></h6>
                    <p class="text-muted small"><?= lang('Admin.socialSharingFbPageHelp') ?></p>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label"><?= lang('Admin.socialSharingFbPageId') ?></label>
                        <div class="col-sm-9"><input type="text" name="fb_page_id" class="form-control"
                            value="<?= esc(setting('Social.facebookPageId')) ?>"></div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label"><?= lang('Admin.socialSharingFbPageToken') ?></label>
                        <div class="col-sm-9"><input type="password" name="fb_page_token" class="form-control"
                            autocomplete="new-password" placeholder="<?= lang('Admin.socialLoginPlaceholderSecret') ?>"></div>
                    </div>

                    <div class="text-right">
                        <button type="submit" class="btn btn-primary"><?= lang('Admin.socialSharingSaveBtn') ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- Spam Protection -->
    <div class="tab-pane fade" id="spam">
        <div class="card shadow mb-4">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><?= lang('Admin.spamProtectionHeading') ?></h6></div>
            <div class="card-body">
                <p><?= lang('Admin.spamHcaptchaIntro') ?></p>
                <p><?= lang('Admin.spamHcaptchaFree') ?></p>
                <form method="POST" action="<?= base_url('admin/settings/spam') ?>">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label for="hcaptcha_site_key"><?= lang('Admin.spamHcaptchaSiteKey') ?></label>
                        <input type="text" class="form-control" id="hcaptcha_site_key" name="hcaptcha_site_key"
                               value="<?= esc(setting('App.hcaptchaSiteKey') ?? '') ?>"
                               placeholder="0x0000000000000000000000000000000000000000">
                    </div>
                    <div class="form-group">
                        <label for="hcaptcha_secret_key"><?= lang('Admin.spamHcaptchaSecretKey') ?></label>
                        <input type="password" class="form-control" id="hcaptcha_secret_key" name="hcaptcha_secret_key"
                               value="<?= esc(setting('App.hcaptchaSecretKey') ?? '') ?>"
                               placeholder="0x0000000000000000000000000000000000000000">
                    </div>
                    <div class="alert alert-info">
                        <?= lang('Admin.spamHcaptchaNote') ?>
                    </div>
                    <button type="submit" class="btn btn-primary"><?= lang('Admin.saveChanges') ?></button>
                </form>
            </div>
        </div>
    </div>

</div>

<?php
// Inject open_tab into page content while PHP is still active, before the NOWDOC
$content = ob_get_clean();
if (! empty($open_tab)) {
    $content .= '<script>window._settingsOpenTab=' . json_encode($open_tab) . ';</script>';
}
?>
<?php $extra_scripts = <<<'SCRIPT'
<script>
function toggleSmtp() {
    var proto = document.querySelector('[name="email_protocol"]').value;
    document.getElementById('smtp-fields').style.display = proto === 'smtp' ? '' : 'none';
}
document.querySelector('[name="email_protocol"]').addEventListener('change', toggleSmtp);
toggleSmtp();

// Activate a specific tab — server open_tab takes priority, then URL hash
(function () {
    var target = (window._settingsOpenTab || window.location.hash.replace('#', ''));
    if (target) {
        $('#settingsTabs a[href="#' + target + '"]').tab('show');
    }
})();
</script>
SCRIPT;
?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content, 'extra_scripts' => $extra_scripts])) ?>
