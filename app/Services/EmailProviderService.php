<?php

namespace App\Services;

use App\Interfaces\EmailProviderInterface;

class EmailProviderService
{
    /** @var array<string, EmailProviderInterface> Registered providers keyed by plugin slug */
    private array $providers = [];

    /**
     * Register a plugin as an email provider.
     * Called from the plugin's register() method.
     */
    public function register(string $slug, EmailProviderInterface $handler): void
    {
        $this->providers[$slug] = $handler;
    }

    /**
     * Return the active provider, or null if core is selected / no provider registered.
     */
    public function getProvider(): ?EmailProviderInterface
    {
        $slug = setting('Email.provider') ?? 'core';

        if ($slug === 'core' || ! isset($this->providers[$slug])) {
            return null;
        }

        return $this->providers[$slug];
    }

    /**
     * All registered providers keyed by slug.
     * Used to build the Settings → Email provider dropdown.
     *
     * @return array<string, EmailProviderInterface>
     */
    public function getRegistered(): array
    {
        return $this->providers;
    }

    /**
     * Check whether any registered provider declares the given capability.
     * Used by PluginManager to detect core email provider conflicts on activation.
     *
     * Capabilities are stored in the plugin's plugin_info.json as:
     *   "capabilities": {"email": ["core", "self"]}
     */
    public function hasRegisteredCoreProvider(): bool
    {
        return ! empty($this->providers);
    }
}
