<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Profiles\Services;

use flight\Engine;

/**
 * ProfileTabService - the tab strip on a user's profile edit pages.
 *
 * Profile and Security are core tabs. A plugin adds its own by registering
 * a label and a callable that returns the tab body. Only the open tab's
 * callable runs.
 *
 * @package Pubvana\Plugins\Profiles
 */
class ProfileTabService
{
    public const TYPE = 'profile.tab';
    public const SLOT = 'default';

    public const CORE_PROFILE_TAB = 'profile';
    public const CORE_SECURITY_TAB = 'security';

    /** @var Engine<object> */
    protected Engine $app;

    /**
     * @param Engine<object> $app
     */
    public function __construct(Engine $app)
    {
        $this->app = $app;
    }

    /**
     * Tabs for a profile edit page.
     *
     * @param \Enlivenapp\FlightShield\Models\User $user   Profile owner
     * @param \Enlivenapp\FlightShield\Models\User $viewer Signed-in user
     *
     * @return list<array{id: string, label: string, active: bool, output: string}>
     */
    public function tabs(object $user, object $viewer, string $open): array
    {
        $tabs = [
            ['id' => self::CORE_PROFILE_TAB, 'label' => 'Profile', 'callable' => null],
        ];

        if ((int) $viewer->id === (int) $user->id) {
            $tabs[] = ['id' => self::CORE_SECURITY_TAB, 'label' => 'Security', 'callable' => null];
        }

        foreach ($this->app->adext()->get(self::TYPE, self::SLOT) as $key => $config) {
            $tabs[] = [
                'id'       => $this->tabId((string) $key),
                'label'    => (string) ($config['label'] ?? ''),
                'callable' => is_callable($config['callable'] ?? null) ? $config['callable'] : null,
            ];
        }

        $ids = array_column($tabs, 'id');
        $active = in_array($open, $ids, true) ? $open : $tabs[0]['id'];

        foreach ($tabs as &$tab) {
            $tab['active'] = $tab['id'] === $active;
            $tab['output'] = $tab['active'] && is_callable($tab['callable'])
                ? (string) $tab['callable']($user)
                : '';
            unset($tab['callable']);
        }
        unset($tab);

        return $tabs;
    }

    /**
     * 'pubvana/addresses' becomes 'addresses'.
     */
    private function tabId(string $key): string
    {
        $parts = explode('/', $key);

        return strtolower((string) end($parts));
    }
}
