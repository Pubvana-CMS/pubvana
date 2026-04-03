<?php

namespace App\Controllers\Admin;

use App\Models\PostRevisionModel;

class Revisions extends BaseAdminController
{
    public function index(int $postId): string
    {
        $db   = db_connect();
        $post = $db->table('posts')->where('id', $postId)->get()->getRowObject();
        if (! $post) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        if (! auth()->user()->can('posts.edit.any') && (int) $post->author_id !== auth()->id()) {
            return redirect()->to('/admin/posts')->with('error', lang('Admin.permissionDenied'));
        }

        $revisions = (new PostRevisionModel())
            ->select('post_revisions.*, u.username as author_name')
            ->join('users u', 'u.id = post_revisions.author_id', 'left')
            ->where('post_revisions.post_id', $postId)
            ->orderBy('post_revisions.id', 'DESC')
            ->findAll();

        return $this->adminView('posts/revisions', array_merge($this->baseData('Revisions — ' . $post->title, 'posts'), [
            'post'      => $post,
            'revisions' => $revisions,
        ]));
    }

    public function show(int $revisionId): string
    {
        $revisionModel = new PostRevisionModel();
        $revision = $revisionModel
            ->select('post_revisions.*, u.username as author_name')
            ->join('users u', 'u.id = post_revisions.author_id', 'left')
            ->where('post_revisions.id', $revisionId)
            ->first();

        if (! $revision) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $post = db_connect()->table('posts')->where('id', $revision->post_id)->get()->getRowObject();
        if ($post && ! auth()->user()->can('posts.edit.any') && (int) $post->author_id !== auth()->id()) {
            return redirect()->to('/admin/posts')->with('error', lang('Admin.permissionDenied'));
        }

        return $this->adminView('posts/revision_show', array_merge($this->baseData('Revision — ' . $revision->title, 'posts'), [
            'revision' => $revision,
            'post'     => $post,
        ]));
    }

    public function restore(int $revisionId)
    {
        $revision = (new PostRevisionModel())->find($revisionId);
        if (! $revision) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        $db   = db_connect();
        $post = $db->table('posts')->where('id', $revision->post_id)->get()->getRowObject();
        if ($post && ! auth()->user()->can('posts.edit.any') && (int) $post->author_id !== auth()->id()) {
            return redirect()->to('/admin/posts')->with('error', lang('Admin.permissionDenied'));
        }

        $db->table('posts')->where('id', $revision->post_id)->update([
            'title'            => $revision->title,
            'content'          => $revision->content,
            'content_type'     => $revision->content_type,
            'excerpt'          => $revision->excerpt,
            'status'           => $revision->status,
            'meta_title'       => $revision->meta_title,
            'meta_description' => $revision->meta_description,
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/admin/posts/' . $revision->post_id . '/revisions')
                         ->with('success', lang('Admin.revisionRestored', [$revision->created_at]));
    }
}
