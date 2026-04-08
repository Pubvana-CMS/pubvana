<?php

namespace App\Controllers\Admin;

use App\Models\BrokenLinkModel;
use App\Services\BrokenLinkService;

class BrokenLinks extends BaseAdminController
{
    public function scan()
    {
        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to('/admin/broken-links')->with('error', lang('Admin.permissionDenied'));
        }

        $result = (new BrokenLinkService())->scan();

        return redirect()->to('/admin/broken-links')->with(
            'success',
            lang('Admin.brokenLinksScanComplete', [$result['total'], $result['broken']])
        );
    }

    public function index(): string
    {
        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }

        $model        = new BrokenLinkModel();
        $showDismissed = (bool) $this->request->getGet('dismissed');
        $rows          = $model->getResults($showDismissed);

        // Group results by source for display
        $grouped = [];
        foreach ($rows as $row) {
            $key = $row->source_type . ':' . $row->source_id;
            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'source_type'  => $row->source_type,
                    'source_id'    => (int) $row->source_id,
                    'source_title' => $row->source_title,
                    'links'        => [],
                ];
            }
            $grouped[$key]['links'][] = $row;
        }

        return $this->adminView('broken_links/index', array_merge(
            $this->baseData('Broken Links', 'broken_links'),
            [
                'grouped'       => $grouped,
                'total'         => count($rows),
                'showDismissed' => $showDismissed,
            ]
        ));
    }

    public function recheck(int $id)
    {
        $model = new BrokenLinkModel();
        $row   = $model->find($id);

        if (! $row) {
            return redirect()->to('/admin/broken-links')->with('error', lang('Admin.notFound'));
        }

        $service = new BrokenLinkService();
        $client  = \Config\Services::curlrequest(['timeout' => 10]);
        $result  = $service->checkUrl($client, $row->url);

        $model->upsert([
            'source_type'   => $row->source_type,
            'source_id'     => (int) $row->source_id,
            'source_title'  => $row->source_title,
            'url'           => $row->url,
            'http_status'   => $result['status'],
            'error_message' => $result['error'] ?? null,
        ]);

        // If it resolved OK, remove the record
        if ($model->isOk($result['status'])) {
            $model->delete($id);
            return redirect()->to('/admin/broken-links')->with('success', lang('Admin.brokenLinkNowReachable'));
        }

        $label = $result['status'] ?? 'unreachable';
        return redirect()->to('/admin/broken-links')->with('error', lang('Admin.brokenLinkStillBroken', [$label]));
    }

    public function dismiss(int $id)
    {
        $model = new BrokenLinkModel();
        $row   = $model->find($id);

        if (! $row) {
            return redirect()->to('/admin/broken-links')->with('error', lang('Admin.notFound'));
        }

        $model->update($id, ['dismissed' => 1]);

        return redirect()->to('/admin/broken-links')->with('success', lang('Admin.brokenLinkDismissed'));
    }

}
