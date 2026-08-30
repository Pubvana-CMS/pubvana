<?php

declare(strict_types=1);

namespace Pubvana\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

/**
 * CreateMailLogsTable - Migration for the mail_logs table.
 *
 * One row per outbound message attempt, written by the Mailer service on
 * every send. Used by the admin Email page (Tools > Email) for the
 * read-only recent-sends list.
 *
 * Schema:
 *   id           - Auto-increment primary key
 *   to_address   - Recipient address
 *   subject      - Message subject
 *   from_address - Envelope From address used (nullable)
 *   transport    - Transport name ('smtp' - the only transport)
 *   status       - 'sent' or 'failed'
 *   error        - SMTP error detail when status = 'failed'
 *   sent_at      - When the attempt happened
 *
 * @package Pubvana\Database\Migrations
 */
class CreateMailLogsTable extends Migration
{
    public function change(): void
    {
        $this->table('mail_logs')
            ->addColumn('id', 'primary')
            ->addColumn('to_address', 'string', ['length' => 500])
            ->addColumn('subject', 'string', ['length' => 500])
            ->addColumn('from_address', 'string', ['length' => 500, 'nullable' => true])
            ->addColumn('transport', 'string', ['length' => 31, 'default' => 'smtp'])
            ->addColumn('status', 'string', ['length' => 31, 'default' => 'sent'])
            ->addColumn('error', 'text', ['nullable' => true])
            ->addColumn('sent_at', 'datetime', ['nullable' => true])
            ->addIndex(['status'])
            ->addIndex(['sent_at'])
            ->create();
    }
}