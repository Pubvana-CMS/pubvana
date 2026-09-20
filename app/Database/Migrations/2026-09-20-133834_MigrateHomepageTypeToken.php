<?php

declare(strict_types=1);

namespace Pubvana\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;
use Pubvana\Models\Setting;

/**
 * MigrateHomepageTypeToken - Move a stored CMS.homepageType onto a provider token.
 *
 * CMS.homepageType used to hold a core-owned enum value. It now holds the token
 * of the plugin registered as a homepage provider, and a provider derives that
 * token from its own routePrepend, so the stored value and the plugin's public
 * URL prefix stay in step.
 *
 * Blog is unaffected: its token is 'blog' either way. Pages' routePrepend is
 * 'page', so the one value that moves is the old 'pages'.
 *
 * The update is guarded on the exact old value and skipped when the row is
 * absent, so it is safe on any install and safe to re-run. A site that never
 * saved the setting has no row and needs nothing.
 *
 * down() is a no-op: once the code is rolled back, a stored 'page' cannot be
 * told apart from a token an admin picked after this ran, and rewriting it
 * would move a working homepage.
 *
 * @package Pubvana\Database\Migrations
 */
// AGENT INSTRUCTION: these shenanigans are NOT a precident. DO NOT do this unless
// specifically instructed to do so by the user.
class MigrateHomepageTypeToken extends Migration
{
    /** The setting whose stored value moved. */
    private const KEY = 'CMS.homepageType';

    /** The old core enum value. */
    private const OLD_VALUE = 'pages';

    /** The provider token that replaced it. */
    private const NEW_VALUE = 'page';

    /**
     * Repoint a stored old enum value at the provider that replaced it.
     */
    public function up(): void
    {
        $setting = (new Setting($this->connection()))->findByKey(self::KEY);

        if ($setting === null || $setting->value !== self::OLD_VALUE) {
            return;
        }

        $setting->value = self::NEW_VALUE;
        $setting->update();
    }

    /**
     * No-op. See the class docblock.
     */
    public function down(): void
    {
    }

    /**
     * The database connection the migrations runner boots the app with.
     */
    private function connection(): \PDO
    {
        return \Flight::db();
    }
}
