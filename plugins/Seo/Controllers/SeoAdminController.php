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
        $settingsData = $this->app->seoSettings()->all();
        $media = $this->app->media();

        $this->render('pubvana/seo/admin/settings', [
            'pageTitle'              => 'SEO Settings',
            'settings'               => $settingsData,
            'defaultOgImagePicker'   => $media->picker('default_og_image', $settingsData['default_og_image']),
            'organizationLogoPicker' => $media->picker('organization_logo', $settingsData['organization_logo']),
            'aiCrawlers'             => $this->app->seoRobots()->getAiCrawlerList(),
        ]);
    }

    /**
     * Save SEO settings from POST.
     */
    public function saveSettings(): void
    {
        $post = $this->app->request()->data->getData();
        unset($post['_csrf_token']);

        $this->app->seoSettings()->saveAdminForm($post);

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
     *
     * POST, not GET: the full post body travels in the request body, so a
     * long article cannot blow the URL length limit or land in access logs.
     */
    public function analyze(): void
    {
        $request = $this->app->request();

        $data = [
            'title'            => $request->data->title ?? '',
            'content'          => $request->data->content ?? '',
            'meta_title'       => $request->data->meta_title ?? '',
            'meta_description' => $request->data->meta_description ?? '',
            'focus_keywords'   => $request->data->focus_keywords ?? [],
            'slug'             => $request->data->slug ?? '',
            'has_images'       => (bool) ($request->data->has_images ?? false),
            'image_alts'       => $request->data->image_alts ?? [],
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
