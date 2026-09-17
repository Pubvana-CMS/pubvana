<?php

declare(strict_types=1);

namespace Pubvana\Plugins\AiAssistant\Controllers;

/**
 * AiCommentsApiController - /api/ai/comments/* endpoints.
 *
 * Lists and moderates comments through the Comments plugin.
 *
 * @package Pubvana\Plugins\AiAssistant\Controllers
 */
class AiCommentsApiController extends AiApiController
{
    /**
     * List comments by status (pending, approved, rejected).
     */
    public function comments(): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'comments.read');

        $query = $this->app->request()->query;
        $page = max(1, (int) ($query->page ?? 1));
        $perPage = min(100, max(1, (int) ($query->per_page ?? 25)));
        $status = $query->status ?? null;
        if ($status !== null && !in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $this->log($key, 'error', 'comment', null, "Invalid status '{$status}'.");
            $this->fail(422, 'status filter must be one of: pending, approved, rejected.');
        }

        $comments = $this->svc('comments');
        $rows = [];
        foreach ($comments->list($page, $perPage, $status !== null ? (string) $status : null) as $comment) {
            $rows[] = $this->app->ai()->serializeComment($comment);
        }

        $this->log($key, 'ok', 'comment', null, 'Listed comments.');
        $this->ok([
            'items'    => $rows,
            'total'    => $comments->countByStatus($status !== null ? (string) $status : null),
            'page'     => $page,
            'per_page' => $perPage,
            'status'   => $status,
        ]);
    }

    /**
     * Approve a comment.
     */
    public function approveComment(string $id): void
    {
        $this->moderateComment((int) $id, 'approve');
    }

    /**
     * Reject a comment.
     */
    public function rejectComment(string $id): void
    {
        $this->moderateComment((int) $id, 'reject');
    }

    /**
     * Delete a comment.
     */
    public function deleteComment(string $id): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'comments.delete');

        if (!$this->svc('comments')->delete((int) $id)) {
            $this->log($key, 'error', 'comment', (int) $id, 'Comment not found.');
            $this->fail(404, 'Comment not found.');
        }

        $this->log($key, 'ok', 'comment', (int) $id, "Deleted comment #{$id}.");
        $this->ok(['deleted' => true, 'id' => (int) $id]);
    }

    /**
     * Shared approve/reject path for comment moderation.
     */
    protected function moderateComment(int $id, string $action): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'comments.' . $action);

        $comment = $action === 'approve'
            ? $this->svc('comments')->approve($id)
            : $this->svc('comments')->reject($id);

        if ($comment === null) {
            $this->log($key, 'error', 'comment', $id, 'Comment not found.');
            $this->fail(404, 'Comment not found.');
        }

        $this->log($key, 'ok', 'comment', $id, ucfirst($action) . 'd comment #' . $id . '.');
        $this->ok($this->app->ai()->serializeComment($comment));
    }
}