<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Marketplace\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

/**
 * package_id is the addon identity: the manifest pubvana.json name
 * ('pubvana/blog'), matching the store catalog slug. folder is install
 * destination bookkeeping only and is never a lookup key.
 */
class AddPackageIdToMarketplaceInstalls extends Migration
{
    public function up(): void
    {
        $this->table('marketplace_installs')
            ->addColumn('package_id', 'string', ['length' => 191, 'nullable' => true, 'default' => null])
            ->addIndex(['package_id'])
            ->update();
    }

    public function down(): void
    {
        $this->table('marketplace_installs')
            ->dropIndex('idx_marketplace_installs_package_id');
        $this->table('marketplace_installs')
            ->dropColumns(['package_id']);
    }
}
