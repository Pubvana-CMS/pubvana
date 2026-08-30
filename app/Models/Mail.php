<?php

declare(strict_types=1);

namespace Pubvana\Models;

/**
 * Mail - ActiveRecord model for the mail_logs table.
 *
 * One row per outbound message attempt. Written by the Mailer service on
 * every send; read by the admin Email page for the recent-sends list.
 *
 * @property int         $id
 * @property string      $to_address
 * @property string      $subject
 * @property string|null $from_address
 * @property string      $transport
 * @property string      $status
 * @property string|null $error
 * @property string|null $sent_at
 */
class Mail extends \flight\ActiveRecord
{
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'mail_logs', $config);
    }

    /**
     * Record one outbound attempt.
     *
     * @param string      $to          Recipient address
     * @param string      $subject     Message subject
     * @param string      $status      'sent' or 'failed'
     * @param string|null $error       Error detail when failed
     * @param string|null $fromAddress Envelope From address used
     * @return self The inserted row
     */
    public function record(string $to, string $subject, string $status, ?string $error = null, ?string $fromAddress = null): self
    {
        $model = new self($this->getDatabaseConnection());
        $model->to_address = $to;
        $model->subject = $subject;
        $model->status = $status;
        $model->error = $error;
        $model->from_address = $fromAddress;
        $model->transport = 'smtp';
        $model->sent_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $model->insert();

        return $model;
    }

    /**
     * Most recent attempts, newest first.
     *
     * @param int $limit
     * @return self[]
     */
    public function recent(int $limit = 15): array
    {
        $model = new self($this->getDatabaseConnection());
        $model->order('sent_at DESC, id DESC');
        if ($limit > 0) {
            $model->limit($limit);
        }

        return $model->findAll();
    }

    /**
     * Count attempts by status.
     *
     * @param string $status 'sent' or 'failed'
     */
    public function countByStatus(string $status): int
    {
        $model = new self($this->getDatabaseConnection());
        $model->eq('status', $status);

        return count($model->findAll());
    }
}