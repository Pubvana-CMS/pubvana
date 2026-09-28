<?php

declare(strict_types=1);

namespace Pubvana\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;
use Pubvana\Models\Setting;

/**
 * RemoveSiteUrlSetting - Retire the CMS.siteUrl row; SITE_URL is env only.
 *
 * SITE_URL is deployment config: it names the origin a deployment serves,
 * is set in .env, and is never edited through the admin UI. The CMS.siteUrl
 * row predates that split, and the settings store reads a DB row before any
 * fallback, so a stored row shadows the deployed value. Reads now go through
 * $app->get('siteUrl'), which env-overrides.php fills from the SITE_URL
 * env var.
 *
 * Safe on any install: the delete matches nothing when the row is absent,
 * so a site that never saved the setting needs no change.
 *
 * down() is a no-op. The deleted value held whatever an admin last saved;
 * restoring it would push a stale origin back over the deployed SITE_URL.
 *
 * @package Pubvana\Database\Migrations
 */
class RemoveSiteUrlSetting extends Migration
{
    /** The setting this migration retires. */
    private const KEY = 'CMS.siteUrl';

    /**
     * Delete the retired setting's row, if present.
     */
    public function up(): void
    {
        (new Setting($this->connection()))->eq('key', self::KEY)->delete();
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
