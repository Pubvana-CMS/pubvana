<?php

declare(strict_types=1);

namespace Pubvana\Plugins\AiAssistant\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

/**
 * Ties ai_key_grants to ai_keys so grants cannot outlive their key.
 *
 * Before this, grants were cleared by hand in AiService::deleteKey(), which
 * meant any delete that did not go through that one call left rows behind.
 * CASCADE makes the database enforce it instead of the service remembering.
 */
class AddAiKeyGrantsForeignKey extends Migration
{
    public function up(): void
    {
        // An orphan grant makes the ALTER fail, so clear them first. Normal
        // for this table, so quiet: every install without orphans hits nothing.
        $this->table('ai_key_grants')
            ->statement(
                'DELETE g FROM ai_key_grants g
                 LEFT JOIN ai_keys k ON k.id = g.key_id
                 WHERE k.id IS NULL'
            );

        // ai_keys.id is INT UNSIGNED because 'primary' builds it that way.
        // key_id was created signed, and MySQL refuses a foreign key between
        // mismatched types. Every stored key_id points at an auto-increment id,
        // so no value is lost by the conversion.
        $this->table('ai_key_grants')
            ->modifyColumn('key_id', 'integer', ['unsigned' => true]);

        $this->table('ai_key_grants')
            ->addForeignKey(['key_id'], 'ai_keys', ['id'], ['delete' => 'CASCADE'])
            ->update();
    }

    public function down(): void
    {
        // Looked up by column, so the down() does not depend on the constraint
        // name the builder generates.
        $this->table('ai_key_grants')
            ->dropForeignKey(['key_id']);
    }
}
