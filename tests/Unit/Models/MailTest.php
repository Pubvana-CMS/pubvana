<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Models;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Models\Mail;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * Mail model over the mail_logs table.
 *
 * @package Pubvana\Tests\Unit\Models
 */
#[CoversClass(Mail::class)]
final class MailTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
    }

    public function testRecordPersistsAttemptWithDefaults(): void
    {
        $mail = (new Mail($this->pdo))->record(
            'to@example.com',
            'Welcome',
            'failed',
            'Connection refused',
            'from@example.com'
        );

        self::assertSame('smtp', $mail->transport);
        self::assertNotNull($mail->sent_at);

        $row = (new Mail($this->pdo))->find($mail->id);
        self::assertInstanceOf(Mail::class, $row);
        self::assertSame('to@example.com', $row->to_address);
        self::assertSame('Welcome', $row->subject);
        self::assertSame('failed', $row->status);
        self::assertSame('Connection refused', $row->error);
        self::assertSame('from@example.com', $row->from_address);
    }

    public function testRecentReturnsNewestFirst(): void
    {
        $this->recordAttempt('first@example.com');
        $this->recordAttempt('second@example.com');
        $this->recordAttempt('third@example.com');

        $rows = (new Mail($this->pdo))->recent();

        self::assertCount(3, $rows);
        self::assertSame('third@example.com', $rows[0]->to_address);
        self::assertSame('first@example.com', $rows[2]->to_address);
    }

    public function testRecentAppliesLimit(): void
    {
        $this->recordAttempt('first@example.com');
        $this->recordAttempt('second@example.com');
        $this->recordAttempt('third@example.com');

        $rows = (new Mail($this->pdo))->recent(2);

        self::assertCount(2, $rows);
        self::assertSame('third@example.com', $rows[0]->to_address);
        self::assertSame('second@example.com', $rows[1]->to_address);
    }

    public function testRecentZeroLimitReturnsAll(): void
    {
        $this->recordAttempt('first@example.com');
        $this->recordAttempt('second@example.com');

        self::assertCount(2, (new Mail($this->pdo))->recent(0));
    }

    public function testCountByStatusSplitsSentAndFailed(): void
    {
        $this->recordAttempt('sent@example.com', 'sent');
        $this->recordAttempt('failed@example.com', 'failed');
        $this->recordAttempt('sent2@example.com', 'sent');

        self::assertSame(2, (new Mail($this->pdo))->countByStatus('sent'));
        self::assertSame(1, (new Mail($this->pdo))->countByStatus('failed'));
        self::assertSame(0, (new Mail($this->pdo))->countByStatus('queued'));
    }

    private function recordAttempt(string $to, string $status = 'sent'): void
    {
        (new Mail($this->pdo))->record($to, 'Subject', $status);
    }
}