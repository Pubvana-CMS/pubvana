<?php

namespace Config;

use CodeIgniter\Events\Events;
use CodeIgniter\Exceptions\FrameworkException;
use CodeIgniter\HotReloader\HotReloader;

/*
 * --------------------------------------------------------------------
 * Application Events
 * --------------------------------------------------------------------
 * Events allow you to tap into the execution of the program without
 * modifying or extending core files. This file provides a central
 * location to define your events, though they can always be added
 * at run-time, also, if needed.
 *
 * You create code that can execute by subscribing to events with
 * the 'on()' method. This accepts any form of callable, including
 * Closures, that will be executed when the event is triggered.
 *
 * Example:
 *      Events::on('create', [$myInstance, 'myMethod']);
 */

Events::on('pre_system', static function (): void {
    if (ENVIRONMENT !== 'testing') {
        if (ini_get('zlib.output_compression')) {
            throw FrameworkException::forEnabledZlibOutputCompression();
        }

        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        ob_start(static fn ($buffer) => $buffer);
    }

    /*
     * --------------------------------------------------------------------
     * Debug Toolbar Listeners.
     * --------------------------------------------------------------------
     * If you delete, they will no longer be collected.
     */
    if (CI_DEBUG && ! is_cli()) {
        Events::on('DBQuery', 'CodeIgniter\Debug\Toolbar\Collectors\Database::collect');
        service('toolbar')->respond();
        // Hot Reload route - for framework use on the hot reloader.
        if (ENVIRONMENT === 'development') {
            service('routes')->get('__hot-reload', static function (): void {
                (new HotReloader())->run();
            });
        }
    }

    /*
     * --------------------------------------------------------------------
     * Locale bootstrap — populate Config\App::$supportedLocales from the
     * languages table BEFORE route matching so the {locale} route segment
     * validation works correctly.
     * --------------------------------------------------------------------
     */
    try {
        $cache  = \Config\Services::cache();
        $codes  = $cache->get('active_languages');

        if ($codes === null) {
            $model = new \App\Models\LanguageModel();
            $langs = $model->getActive();
            $codes = array_map(static fn (object $l): string => $l->code, $langs);
            // Ensure default locale is always included
            if (! in_array('en', $codes, true)) {
                $codes[] = 'en';
            }
            $cache->save('active_languages', $codes, 3600);

            // Cache the default locale too
            $defaultLang = $model->getDefault();
            if ($defaultLang) {
                $cache->save('default_locale', $defaultLang->code, 3600);
            }
        }

        if (! empty($codes)) {
            config('App')->supportedLocales = $codes;

            // Set default locale from DB
            $defaultLocale = $cache->get('default_locale');
            if ($defaultLocale) {
                config('App')->defaultLocale = $defaultLocale;
            }

            // The IncomingRequest copied supportedLocales at construction time
            // (before this event), so its internal validLocales is stale.
            // Sync it now so setLocale() accepts the DB-driven codes.
            $request = service('request');
            $request->setValidLocales($codes);

            // Set the default locale on the request — the router will override
            // this if a {locale} segment is matched in the URL.
            if ($defaultLocale ?? null) {
                $request->setLocale($defaultLocale);
            }
        }
    } catch (\Throwable $e) {
        // DB may not be available (e.g. CLI spark commands during install)
        // Leave supportedLocales at its default value.
        log_message('debug', 'pre_system locale bootstrap skipped: ' . $e->getMessage());
    }

    /*
     * --------------------------------------------------------------------
     * Plugin System — boot active plugins so namespaces, routes, and
     * CSRF exemptions are registered before the request is handled.
     * --------------------------------------------------------------------
     */
    try {
        \App\Services\PluginManager::instance()->boot();
    } catch (\Throwable $e) {
        log_message('debug', 'pre_system plugin boot skipped: ' . $e->getMessage());
    }
});
