<?php

namespace App\Controllers\Admin;

use App\Models\MediaModel;
use App\Services\MediaService;

class Media extends BaseAdminController
{
    public function index(): string
    {
        if (! auth()->user()->can('media.upload')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }
        $model  = new MediaModel();
        $media  = $model->orderBy('created_at', 'DESC')->paginate(24);
        return $this->adminView('media/index', array_merge($this->baseData('Media Library', 'media'), [
            'media' => $media,
            'pager' => $model->pager,
        ]));
    }

    public function upload()
    {
        if (! auth()->user()->can('media.upload')) {
            return $this->response->setJSON(['error' => 'Permission denied.'])->setStatusCode(403);
        }
        $file = $this->request->getFile('file');
        if (! $file || ! $file->isValid()) {
            return $this->response->setJSON(['error' => 'No valid file uploaded.'])->setStatusCode(400);
        }
        try {
            $altText = (string) $this->request->getPost('alt_text');
            $title   = (string) $this->request->getPost('title');
            $service = new MediaService();
            $result  = $service->upload($file, (int) auth()->id(), $altText, $title);
            return $this->response->setJSON([
                'success'    => true,
                'id'         => $result['id'],
                'url'        => $result['url'],
                'path'       => $result['path'],
                'filename'   => $result['filename'],
                'alt_text'   => $result['alt_text'],
                'title'      => $result['title'],
                'thumb_path' => $result['thumb_path'],
            ]);
        } catch (\RuntimeException $e) {
            return $this->response->setJSON(['error' => $e->getMessage()])->setStatusCode(422);
        }
    }

    public function json()
    {
        if (! auth()->user()->can('media.upload')) {
            return $this->response->setJSON(['error' => 'Permission denied.'])->setStatusCode(403);
        }
        $model = new MediaModel();
        $page  = (int) ($this->request->getGet('page') ?: 1);
        $items = $model->orderBy('created_at', 'DESC')->paginate(24, 'default', $page);
        $data  = [];
        foreach ($items as $item) {
            $dir        = dirname($item->path);
            $filename   = basename($item->path);
            $thumbPath  = $dir . '/thumbs/' . $filename;
            $data[] = [
                'id'         => $item->id,
                'filename'   => $item->filename,
                'path'       => $item->path,
                'mime_type'  => $item->mime_type,
                'alt_text'   => $item->alt_text,
                'title'      => $item->title,
                'thumb_path' => $thumbPath,
                'created_at' => $item->created_at,
            ];
        }
        return $this->response->setJSON([
            'data'  => $data,
            'total' => $model->pager->getTotal(),
            'page'  => $page,
            'pages' => $model->pager->getPageCount(),
        ]);
    }

    public function update(int $id)
    {
        if (! auth()->user()->can('media.upload')) {
            return $this->response->setJSON(['success' => false, 'error' => 'Permission denied.'])->setStatusCode(403);
        }
        $model = new MediaModel();
        $media = $model->find($id);
        if (! $media) {
            return $this->response->setJSON(['success' => false, 'error' => 'Media not found.'])->setStatusCode(404);
        }
        $data = [];
        if ($this->request->getPost('alt_text') !== null) {
            $data['alt_text'] = (string) $this->request->getPost('alt_text');
        }
        if ($this->request->getPost('title') !== null) {
            $data['title'] = (string) $this->request->getPost('title');
        }
        if (empty($data)) {
            return $this->response->setJSON(['success' => true]);
        }
        $model->update($id, $data);
        return $this->response->setJSON(['success' => true]);
    }

    public function delete(int $id)
    {
        if (! auth()->user()->can('media.upload')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }
        (new MediaService())->delete($id);
        return redirect()->to('/admin/media')->with('success', lang('Admin.mediaDeleted'));
    }
}
