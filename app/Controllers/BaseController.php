<?php

namespace App\Controllers;

use App\Libraries\LanguageSwitcher;
use App\Models\LanguageModel;
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

        // Locale detection — CI4 automatically calls $request->setLocale() when
        // a {locale} route segment is matched, so getLocale() returns the correct
        // locale (or the app defaultLocale when no locale prefix was used).
        $locale = $this->request->getLocale();
        if (empty($locale)) {
            $locale = config('App')->defaultLocale;
        }
        service('language')->setLocale($locale);

        try {
            (new \App\Services\MarketplaceService())->checkAndRevalidateIfDue();
        } catch (\Throwable $e) {
            log_message('error', 'BaseController: license revalidation error: ' . $e->getMessage());
        }
    }

    /**
     * Build the language switcher data and pass it to ThemeService.
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
            $currentLocale = $this->request->getLocale() ?: config('App')->defaultLocale;

            $switcher = new LanguageSwitcher($languages, $currentUri, $currentLocale);
            $this->themeService->setLangSwitcher($switcher->build());
        } catch (\Throwable $e) {
            log_message('error', 'buildLangSwitcher failed: ' . $e->getMessage());
        }
    }
}
