<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Pages\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

/**
 * AddPagesPreviewToken - adds the token column for draft preview links.
 *
 * The token is 64 hex characters and unique. Existing rows start NULL, and
 * the admin preview route issues a token on the first click. MySQL allows
 * many NULLs in a unique index.
 *
 * @package Pubvana\Plugins\Pages\Database\Migrations
 */
class AddPagesPreviewToken extends Migration
{
    public function up(): void
    {
        $this->table('pages')->addColumn('preview_token', 'string', [
            'length'   => 64,
            'nullable' => true,
            'default'  => null,
        ])->addColumns();

        $this->table('pages')->statement(
            'ALTER TABLE `pages` ADD UNIQUE KEY `idx_pages_preview_token` (`preview_token`)'
        );
    }

    public function down(): void
    {
        $this->table('pages')->dropIndex('idx_pages_preview_token');
        $this->table('pages')->dropColumns(['preview_token']);
    }
}
