<?php

namespace App\Controllers\Admin;

use App\Models\AdminNotificationModel;

class Notifications extends BaseAdminController
{
    public function dismiss(int $id)
    {
        $model = new AdminNotificationModel();

        if (! $model->dismiss($id)) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Notification not found.']);
        }

        return $this->response->setJSON(['success' => true]);
    }
}
