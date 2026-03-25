<?php

namespace App\Controllers;

use App\Libraries\LanguageSwitcher;
use App\Models\LanguageModel;
use App\Models\NavigationModel;
use App\Models\SocialModel;
use App\Services\PluginManager;
use App\Services\ThemeService;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    protected array $data = [];
    protected ThemeService $themeService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->themeService = new ThemeService();

        $this->data['theme']        = $this->themeService->getActive();
        $this->data['site_name']    = site_name();
        $this->data['site_tagline'] = site_tagline();
        $this->data['settings']     = [];

        // Locale detection — CI4 automatically calls $request->setLocale() when
        // a {locale} route segment is matched, so getLocale() returns the correct
        // locale (or the app defaultLocale when no locale prefix was used).
        $locale = $this->request->getLocale();
        if (empty($locale)) {
            $locale = config('App')->defaultLocale;
        }
        service('language')->setLocale($locale);
        $this->data['current_locale'] = $locale;

        // Default: empty lang switcher; public controllers will populate it
        // via buildLangSwitcher() when they have a concrete URI to work with.
        $this->data['langSwitcher'] = [];

        try {
            $navModel = new NavigationModel();
            $this->data['primary_nav'] = $navModel->where('nav_group', 'primary')->orderBy('sort_order')->findAll();
            $this->data['footer_nav']  = $navModel->where('nav_group', 'footer')->orderBy('sort_order')->findAll();

            $socialModel = new SocialModel();
            $this->data['social_links'] = $socialModel->where('is_active', 1)->orderBy('sort_order')->findAll();
        } catch (\Throwable $e) {
            $this->data['primary_nav']  = [];
            $this->data['footer_nav']   = [];
            $this->data['social_links'] = [];
        }

        try {
            $pm = PluginManager::instance();
            $pm->loadAll();
            $this->data['plugin_menu_items'] = $pm->getMenuItems();
        } catch (\Throwable $e) {
            $this->data['plugin_menu_items'] = [];
        }

        try {
            (new \App\Services\MarketplaceService())->checkAndRevalidateIfDue();
        } catch (\Throwable $e) {
            log_message('error', 'BaseController: license revalidation error: ' . $e->getMessage());
        }
    }

    /**
     * Build the language switcher data and merge it into $this->data.
     *
     * Call this from public controller methods (Blog, Pages, Contact, etc.)
     * AFTER the locale is resolved so the current URI is known.
     * Admin controllers should NOT call this.
     */
    protected function buildLangSwitcher(): void
    {
        try {
            $cache     = service('cache');
            $languages = $cache->get('active_languages_objects');

            if ($languages === null) {
                $model     = new LanguageModel();
                $languages = $model->getActive();
                $cache->save('active_languages_objects', $languages, 3600);
            }

            if (empty($languages)) {
                return;
            }

            $currentUri    = '/' . ltrim($this->request->getUri()->getPath(), '/');
            $currentLocale = $this->data['current_locale'] ?? config('App')->defaultLocale;

            $switcher = new LanguageSwitcher($languages, $currentUri, $currentLocale);
            $this->data['langSwitcher'] = $switcher->build();
        } catch (\Throwable $e) {
            log_message('error', 'buildLangSwitcher failed: ' . $e->getMessage());
            $this->data['langSwitcher'] = [];
        }
    }
}
