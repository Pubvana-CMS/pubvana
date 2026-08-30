<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Seo\Controllers;

use Pubvana\Controllers\Admin\AdminController;

/**
 * Admin controller for SEO settings and content-level SEO management.
 */
class SeoAdminController extends AdminController
{
    /**
     * Show the SEO settings page.
     */
    public function settings(): void
    {
        $settings = $this->app->settings();
        $robots = $this->app->seoRobots();
        $media = $this->app->media();

        $settingsData = [
            'title_separator'        => $settings->get('Seo.title_separator', '|'),
            'title_template'         => $settings->get('Seo.title_template', '{title} {sep} {site_name}'),
            'default_og_image'       => $settings->get('Seo.default_og_image', ''),
            'default_language'       => $settings->get('Seo.default_language', 'en'),
            'organization_name'      => $settings->get('Seo.organization_name', ''),
            'organization_logo'      => $settings->get('Seo.organization_logo', ''),
            'social_profiles'        => $settings->get('Seo.social_profiles', []),
            'verification_google'    => $settings->get('Seo.verification_google', ''),
            'verification_bing'      => $settings->get('Seo.verification_bing', ''),
            'robots_txt_custom'      => $settings->get('Seo.robots_txt_custom', ''),
            'sitemap_enabled'        => $settings->get('Seo.sitemap_enabled', true),
            'sitemap_include_pages'  => $settings->get('Seo.sitemap_include_pages', true),
            'sitemap_include_posts'  => $settings->get('Seo.sitemap_include_posts', true),
            'llms_txt_enabled'       => $settings->get('Seo.llms_txt_enabled', true),
            'llms_txt_include_pages' => $settings->get('Seo.llms_txt_include_pages', true),
            'llms_txt_include_posts' => $settings->get('Seo.llms_txt_include_posts', true),
            'ai_disclosure_enabled'  => $settings->get('Seo.ai_disclosure_enabled', true),
        ];

        $this->render('pubvana/seo/admin/settings', [
            'pageTitle'              => 'SEO Settings',
            'settings'               => $settingsData,
            'defaultOgImagePicker'   => $media->picker('default_og_image', $settingsData['default_og_image']),
            'organizationLogoPicker' => $media->picker('organization_logo', $settingsData['organization_logo']),
            'aiCrawlers'             => $robots->getAiCrawlerList(),
        ]);
    }

    /**
     * Save SEO settings from POST.
     */
    public function saveSettings(): void
    {
        $post = $this->app->request()->data->getData();
        unset($post['_csrf_token']);

        $settings = $this->app->settings();

        // Simple string/boolean settings
        $stringKeys = [
            'title_separator', 'title_template', 'default_og_image',
            'default_language', 'organization_name', 'organization_logo',
            'verification_google', 'verification_bing', 'robots_txt_custom',
        ];

        foreach ($stringKeys as $key) {
            if (array_key_exists($key, $post)) {
                $settings->set('Seo.' . $key, $post[$key]);
            }
        }

        // Boolean settings
        $boolKeys = [
            'sitemap_enabled', 'sitemap_include_pages', 'sitemap_include_posts',
            'llms_txt_enabled', 'llms_txt_include_pages', 'llms_txt_include_posts',
            'ai_disclosure_enabled',
        ];

        foreach ($boolKeys as $key) {
            $settings->set('Seo.' . $key, isset($post[$key]) ? true : false);
        }

        // Social profiles (textarea, one URL per line)
        if (isset($post['social_profiles'])) {
            $profiles = array_filter(array_map('trim', explode("\n", $post['social_profiles'])));
            $settings->set('Seo.social_profiles', array_values($profiles));
        }

        // AI crawler settings
        if (isset($post['ai_crawlers']) && is_array($post['ai_crawlers'])) {
            foreach ($post['ai_crawlers'] as $bot => $stance) {
                $settingKey = 'Seo.ai_crawler_' . strtolower(str_replace(['-', ' '], '_', $bot));
                $settings->set($settingKey, $stance === 'allow' ? 'allow' : 'block');
            }
        }

        $this->app->session()->flash('success', 'SEO settings saved.');
        $this->app->redirect('/admin/seo');
    }

    /**
     * Save SEO meta for a content item (AJAX from edit forms).
     */
    public function saveMeta(): void
    {
        $post = $this->app->request()->data->getData();
        unset($post['_csrf_token']);

        $contentType = $post['content_type'] ?? '';
        $contentId = (int) ($post['content_id'] ?? 0);

        if (empty($contentType) || $contentId <= 0) {
            $this->app->json(['error' => 'Missing content_type or content_id'], 400);
            return;
        }

        unset($post['content_type'], $post['content_id']);

        $meta = $this->app->seo()->saveMeta($contentType, $contentId, $post);

        $this->app->json([
            'success' => true,
            'id'      => $meta->id,
            'score'   => $meta->seo_score,
        ]);
    }

    /**
     * Run content analysis (AJAX endpoint).
     */
    public function analyze(): void
    {
        $request = $this->app->request();

        $data = [
            'title'            => $request->query->title ?? '',
            'content'          => $request->query->content ?? '',
            'meta_title'       => $request->query->meta_title ?? '',
            'meta_description' => $request->query->meta_description ?? '',
            'focus_keywords'   => $request->query->focus_keywords ?? [],
            'slug'             => $request->query->slug ?? '',
            'has_images'       => (bool) ($request->query->has_images ?? false),
            'image_alts'       => $request->query->image_alts ?? [],
        ];

        // Parse focus_keywords if it comes as comma-separated string
        if (is_string($data['focus_keywords'])) {
            $data['focus_keywords'] = array_filter(array_map('trim', explode(',', $data['focus_keywords'])));
        }

        $analysis = $this->app->seoAnalysis();
        $result = $analysis->analyze($data);

        $this->app->json($result);
    }
}
