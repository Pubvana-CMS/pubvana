<?php

namespace App\Models;

use CodeIgniter\Model;

class AdminNotificationModel extends Model
{
    protected $table         = 'admin_notifications';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'source',
        'source_name',
        'severity',
        'message',
        'action_url',
        'action_label',
        'is_dismissable',
        'dismissed_at',
    ];

    /**
     * Return all active (undismissed) notifications, newest first.
     *
     * @return array<\stdClass>
     */
    public function getActive(): array
    {
        return $this->where('dismissed_at', null)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    /**
     * Dismiss a notification by ID.
     * Returns false if not found or already dismissed.
     */
    public function dismiss(int $id): bool
    {
        $notification = $this->find($id);

        if (! $notification || $notification->dismissed_at !== null) {
            return false;
        }

        return $this->update($id, [
            'dismissed_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
