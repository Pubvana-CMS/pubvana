<?php

namespace App\Interfaces;

interface PluginInterface
{
    /**
     * Human-readable name, e.g. 'E-commerce'.
     */
    public function getName(): string;

    /**
     * URL-safe identifier, e.g. 'ecommerce'.
     */
    public function getSlug(): string;

    /**
     * Semantic version string, e.g. '1.0.0'.
     */
    public function getVersion(): string;

    /**
     * Admin sidebar section for this plugin.
     *
     * Returns a single top-level collapsible section with child links.
     * Return an empty array if the plugin has no admin pages.
     *
     * Structure:
     *   [
     *       'label'    => string  Plugin name shown in sidebar (e.g. 'Digital Store')
     *       'icon'     => string  Font Awesome 5 class (e.g. 'fas fa-store')
     *       'children' => [
     *           ['label' => string, 'url' => string, 'nav_key' => string],
     *           ...
     *       ]
     *   ]
     *
     * nav_key: a unique string per child page (e.g. 'dstore_products').
     * The admin layout uses this to highlight the active link and keep
     * the section open. Pass it as 'active_nav' from your controller.
     *
     * @return array
     */
    public function getMenuItems(): array;

    /**
     * Route patterns to exempt from CSRF protection.
     *
     * Typically webhook endpoints that receive external POST requests
     * (e.g. Stripe, PayPal) where CSRF tokens cannot be included.
     *
     * @return string[] Route patterns, e.g. ['dstore/webhooks/*']
     */
    public function getCsrfExemptions(): array;

    /**
     * Called once per request when the plugin is active.
     * Hook into services, events, or register resources here.
     */
    public function register(): void;
}
