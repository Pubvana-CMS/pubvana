<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Marketplace\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

class CreateMarketplaceInstallsTable extends Migration
{
    public function up(): void
    {
        $this->table('marketplace_installs')
            ->addColumn('id', 'primary', [])
            ->addColumn('store_product_id', 'integer', [])
            ->addColumn('product_name', 'string', ['length' => 255])
            ->addColumn('slug', 'string', ['length' => 255, 'nullable' => true, 'default' => null])
            ->addColumn('item_type', 'enum', ['values' => ['plugin', 'theme', 'file'], 'default' => 'plugin'])
            ->addColumn('folder', 'string', ['length' => 255, 'nullable' => true, 'default' => null])
            ->addColumn('installed_version', 'string', ['length' => 32, 'nullable' => true, 'default' => null])
            ->addColumn('license_key', 'string', ['length' => 64, 'nullable' => true, 'default' => null])
            ->addColumn('license_scope', 'enum', ['values' => ['single_site', 'multi_site', 'none'], 'default' => 'single_site'])
            ->addColumn('license_valid', 'boolean', ['default' => false])
            ->addColumn('license_last_checked', 'datetime', ['nullable' => true, 'default' => null])
            ->addColumn('expires_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addColumn('renews_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addColumn('is_subscription', 'boolean', ['default' => false])
            ->addColumn('registered_domain', 'string', ['length' => 255, 'nullable' => true, 'default' => null])
            ->addColumn('created_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addColumn('updated_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addIndex(['store_product_id'], ['unique' => true])
            ->addIndex(['license_key'])
            ->create();
    }

    public function down(): void
    {
        $this->table('marketplace_installs')->drop();
    }
}
