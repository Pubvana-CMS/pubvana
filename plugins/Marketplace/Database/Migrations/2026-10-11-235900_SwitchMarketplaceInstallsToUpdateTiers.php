<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Marketplace\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

/**
 * The marketplace ledger's October rework, consolidated into one migration.
 *
 * Takes the schema from CreateMarketplaceInstallsTable to its final shape:
 * the update tier replaces the scope, expiry, renewal, and subscription
 * columns; the unique identity moves from the product to the license; and
 * installed_version drops, because the installed version is read live from
 * each addon's pubvana.json and never stored.
 *
 * The name is kept from the first step of the rework on purpose: the
 * runner keys executed migrations by filename timestamp, so a database
 * that already ran the interim chain (Switch, Key, Unique, Rename, Drop)
 * skips this file, while a database that has not, and every fresh
 * install, runs the whole delta once.
 *
 * The license unique goes on with a statement: the builder's addIndex()
 * only feeds create(), so on an existing table only statement() lands.
 */
class SwitchMarketplaceInstallsToUpdateTiers extends Migration
{
    public function up(): void
    {
        $this->table('marketplace_installs')
            ->addColumn('tier', 'enum', [
                'values'   => ['patch', 'minor', 'major'],
                'nullable' => true,
                'default'  => null,
                'after'    => 'license_key',
            ])
            ->addColumns();

        $this->table('marketplace_installs')->dropColumns([
            'installed_version', 'license_scope', 'expires_at', 'renews_at', 'is_subscription',
        ]);

        $this->table('marketplace_installs')->dropIndex(['store_product_id']);
        $this->table('marketplace_installs')->dropIndex(['license_key']);
        $this->table('marketplace_installs')->statement(
            'ALTER TABLE `marketplace_installs` ADD UNIQUE KEY `idx_marketplace_installs_license_key` (`license_key`)'
        );
    }

    public function down(): void
    {
        $this->table('marketplace_installs')->statement(
            'ALTER TABLE `marketplace_installs` DROP INDEX `idx_marketplace_installs_license_key`'
        );
        $this->table('marketplace_installs')->statement(
            'ALTER TABLE `marketplace_installs` ADD UNIQUE KEY `idx_marketplace_installs_store_product_id` (`store_product_id`)'
        );
        $this->table('marketplace_installs')->statement(
            'ALTER TABLE `marketplace_installs` ADD KEY `idx_marketplace_installs_license_key` (`license_key`)'
        );

        $this->table('marketplace_installs')
            ->addColumn('installed_version', 'string', ['length' => 32, 'nullable' => true, 'default' => null])
            ->addColumn('license_scope', 'enum', ['values' => ['single_site', 'multi_site', 'none'], 'default' => 'single_site'])
            ->addColumn('expires_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addColumn('renews_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addColumn('is_subscription', 'boolean', ['default' => false])
            ->addColumns();

        $this->table('marketplace_installs')->dropColumns(['tier']);
    }
}
