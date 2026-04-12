<?php

namespace App\Controllers\Admin;

use App\Models\PageModel;
use App\Services\ActivityLogger;
use App\Services\PluginManager;

class Settings extends BaseAdminController
{
    public function index(): string
    {
        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }

        $pages = (new PageModel())->where('status', 'published')->findAll();

        try {
            $pluginRoutes = PluginManager::instance()->getPublicRoutes();
        } catch (\Throwable $e) {
            $pluginRoutes = [];
        }

        return $this->adminView('settings/index', array_merge($this->baseData('Settings', 'settings'), [
            'pages'             => $pages,
            'pluginRoutes'      => $pluginRoutes,
            'emailProviders'    => service('emailProvider')->getRegistered(),
        ]));
    }

    public function saveGeneral()
    {
        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }
        setting()->set('App.siteName',      $this->request->getPost('site_name'));
        setting()->set('App.siteTagline',   $this->request->getPost('site_tagline'));
        setting()->set('App.siteEmail',     $this->request->getPost('site_email'));
        setting()->set('App.postsPerPage',  (int) $this->request->getPost('posts_per_page'));
        setting()->set('App.commentsEnabled', (bool) $this->request->getPost('comments_enabled'));
        setting()->set('App.commentModeration', (bool) $this->request->getPost('comment_moderation'));
        setting()->set('App.maintenanceMode', (bool) $this->request->getPost('maintenance_mode'));
        setting()->set('App.pageCacheTtl', (int) $this->request->getPost('page_cache_ttl'));

        $fpType = $this->request->getPost('front_page_type');
        if (! in_array($fpType, ['blog', 'page', 'plugin_route'], true)) {
            $fpType = 'blog';
        }
        $fpId    = $this->request->getPost('front_page_id');
        $fpRoute = $this->request->getPost('front_page_route');
        setting()->set('App.frontPageType',  $fpType);
        setting()->set('App.frontPageId',    ($fpType === 'page' && $fpId) ? (int) $fpId : null);
        setting()->set('App.frontPageRoute', ($fpType === 'plugin_route' && $fpRoute) ? trim($fpRoute) : null);

        ActivityLogger::log('settings.updated', 'setting', null, 'Updated general settings');
        return redirect()->to('/admin/settings')->with('success', lang('Admin.generalSettingsSaved'));
    }

    public function saveSeo()
    {
        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }
        setting()->set('Seo.metaDescription',    $this->request->getPost('meta_description'));
        setting()->set('Seo.googleAnalytics',    $this->request->getPost('google_analytics'));
        setting()->set('Seo.sitemapEnabled',     (bool) $this->request->getPost('sitemap_enabled'));
        setting()->set('Seo.newsSitemapEnabled', (bool) $this->request->getPost('news_sitemap_enabled'));
        return redirect()->to('/admin/settings#seo')->with('success', lang('Admin.seoSettingsSaved'));
    }

    public function saveEmail()
    {
        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }
        setting()->set('Email.fromName',  $this->request->getPost('email_from_name'));
        setting()->set('Email.fromAddress', $this->request->getPost('email_from_address'));
        setting()->set('Email.protocol',  $this->request->getPost('email_protocol'));
        setting()->set('Email.SMTPHost',  $this->request->getPost('smtp_host'));
        setting()->set('Email.SMTPPort',  (int) ($this->request->getPost('smtp_port') ?: 587));
        setting()->set('Email.SMTPCrypto', $this->request->getPost('smtp_crypto'));
        setting()->set('Email.SMTPUser',  $this->request->getPost('smtp_user'));

        $pass = $this->request->getPost('smtp_pass');
        if ($pass !== null && $pass !== '') {
            setting()->set('Email.SMTPPass', $pass);
        }

        $provider = $this->request->getPost('email_provider');
        if ($provider !== null) {
            setting()->set('Email.provider', $provider);
        }

        ActivityLogger::log('settings.updated', 'setting', null, 'Updated email settings');
        return redirect()->to('/admin/settings#email')->with('success', lang('Admin.emailSettingsSaved'));
    }

    public function saveSpam()
    {
        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }
        setting()->set('App.hcaptchaSiteKey',   trim($this->request->getPost('hcaptcha_site_key') ?? ''));
        setting()->set('App.hcaptchaSecretKey',  trim($this->request->getPost('hcaptcha_secret_key') ?? ''));
        ActivityLogger::log('settings.updated', 'setting', null, 'Updated spam protection settings');
        return redirect()->to('/admin/settings#spam')->with('success', lang('Admin.spamSettingsSaved'));
    }

    public function saveSocial()
    {
        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }

        setting()->set('Social.googleClientId',      $this->request->getPost('google_client_id') ?? '');
        setting()->set('Social.facebookClientId',    $this->request->getPost('facebook_client_id') ?? '');

        $googleSecret = $this->request->getPost('google_client_secret');
        if ($googleSecret !== null && $googleSecret !== '') {
            setting()->set('Social.googleClientSecret', $googleSecret);
        }
        $fbSecret = $this->request->getPost('facebook_client_secret');
        if ($fbSecret !== null && $fbSecret !== '') {
            setting()->set('Social.facebookClientSecret', $fbSecret);
        }

        ActivityLogger::log('settings.updated', 'setting', null, 'Updated social login settings');
        return redirect()->to('/admin/settings#social')->with('success', lang('Admin.socialLoginSettingsSaved'));
    }

    public function saveSocialSharing()
    {
        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }

        setting()->set('Social.facebookPageId', $this->request->getPost('fb_page_id') ?? '');

        $fields = [
            'twitter_api_key'      => 'Social.twitterApiKey',
            'twitter_api_secret'   => 'Social.twitterApiSecret',
            'twitter_access_token' => 'Social.twitterAccessToken',
            'twitter_access_secret'=> 'Social.twitterAccessSecret',
            'fb_page_token'        => 'Social.facebookPageToken',
        ];
        foreach ($fields as $formField => $settingKey) {
            $val = $this->request->getPost($formField);
            if ($val !== null && $val !== '') {
                setting()->set($settingKey, $val);
            }
        }

        ActivityLogger::log('settings.updated', 'setting', null, 'Updated social sharing settings');
        return redirect()->to('/admin/settings#sharing')->with('success', lang('Admin.socialSharingSettingsSaved'));
    }
}
