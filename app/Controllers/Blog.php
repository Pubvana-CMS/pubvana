<?php

namespace App\Controllers;

use App\Models\AuthorProfileModel;
use App\Models\CategoryModel;
use App\Models\CommentModel;
use App\Models\PostModel;
use App\Models\TagModel;
use App\Services\HCaptchaService;
use App\Services\OgImageService;
use App\Services\SeoService;

class Blog extends BaseController
{
    protected PostModel    $postModel;
    protected SeoService   $seoService;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->postModel  = new PostModel();
        $this->seoService = new SeoService();
    }

    public function index(): string
    {
        $this->buildLangSwitcher();

        // Configurable front page
        $frontPageType = setting('App.frontPageType') ?? 'blog';
        if ($frontPageType === 'page') {
            $pageId = setting('App.frontPageId');
            if ($pageId) {
                $page = (new \App\Models\PageModel())->find($pageId);
                if ($page && $page->status === 'published') {
                    return $this->themeService->view('page', [
                        'page' => $page,
                        'seo'  => $this->seoService->getMeta($page),
                    ]);
                }
            }
        }

        $perPage = (int) (setting('App.postsPerPage') ?? 10);
        $posts   = $this->postModel->published()
            ->orderBy('published_at', 'DESC')
            ->paginate($perPage, 'default');

        return $this->themeService->view('home', [
            'posts' => $posts,
            'pager' => $this->postModel->pager,
            'seo'   => $this->seoService->getDefaultMeta(),
        ]);
    }

    public function post(string $slug)
    {
        $this->buildLangSwitcher();

        $post = $this->postModel->published()->findBySlug($slug);
        if (! $post) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $this->postModel->incrementViews($post->id);
        $this->logPageView((int) $post->id, 'post');

        $commentModel = new CommentModel();
        $comments     = $commentModel->getTree((int) $post->id);

        // Handle comment submission
        $commentSaved = false;
        if (strtolower($this->request->getMethod()) === 'post' && setting('App.commentsEnabled')) {
            if (! auth()->loggedIn()) {
                return redirect()->to('/login')->with('error', lang('Blog.commentLoginToComment'));
            }

            $throttler = \Config\Services::throttler();
            if (! $throttler->check('comment_' . auth()->id(), 5, MINUTE * 10)) {
                return $this->response->setStatusCode(429)
                    ->setBody('<h1>Too Many Requests</h1><p>You are commenting too quickly. Please wait a few minutes before trying again.</p>');
            }

            $commentSaved = $this->handleComment($post);
            if ($commentSaved) {
                $msg = setting('App.commentModeration')
                    ? lang('Blog.commentAwaitModeration')
                    : lang('Blog.commentPosted');
                return redirect()->to(post_url($slug) . '#comments')->with('success', $msg);
            }
        }

        $authorProfile = null;
        if ($post->author_id) {
            $profileModel  = new AuthorProfileModel();
            $profile       = $profileModel->getByUserId((int) $post->author_id);
            if ($profile) {
                // Attach username/email from users table for gravatar fallback
                $userRow = db_connect()->table('users u')
                    ->select('u.username, ai.secret AS email')
                    ->join('auth_identities ai', 'ai.user_id = u.id AND ai.type = \'email_password\'', 'left')
                    ->where('u.id', $post->author_id)
                    ->get()->getRowObject();
                if ($userRow) {
                    $profile->username = $userRow->username;
                    $profile->email    = $userRow->email;
                }
                $authorProfile = $profile;
            }
        }

        $seo = $this->seoService->getMeta($post);
        if (empty($seo['og_image'])) {
            $seo['og_image'] = (new OgImageService())->generate($post->title, $post->slug);
        }

        $paywall = ! empty($post->is_premium) && ! auth()->loggedIn();

        $wordCount = str_word_count(strip_tags($post->content ?? ''));
        $readingTime = max(1, (int) ceil($wordCount / 200));

        return $this->themeService->view('post', [
            'post'           => $post,
            'comments'       => $comments,
            'author_profile' => $authorProfile,
            'seo'            => $seo,
            'json_ld'        => $this->seoService->getJsonLd($post, $authorProfile),
            'paywall'        => $paywall,
            'reading_time'   => $readingTime,
        ]);
    }

    public function preview(string $token)
    {
        $this->buildLangSwitcher();

        $post = $this->postModel->findByPreviewToken($token);
        if (! $post) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $commentModel = new CommentModel();
        $comments     = $commentModel->getTree((int) $post->id);

        $authorProfile = null;
        if ($post->author_id) {
            $profileModel = new AuthorProfileModel();
            $profile      = $profileModel->getByUserId((int) $post->author_id);
            if ($profile) {
                $userRow = db_connect()->table('users u')
                    ->select('u.username, ai.secret AS email')
                    ->join('auth_identities ai', 'ai.user_id = u.id AND ai.type = \'email_password\'', 'left')
                    ->where('u.id', $post->author_id)
                    ->get()->getRowObject();
                if ($userRow) {
                    $profile->username = $userRow->username;
                    $profile->email    = $userRow->email;
                }
                $authorProfile = $profile;
            }
        }

        $wordCount = str_word_count(strip_tags($post->content ?? ''));
        $readingTime = max(1, (int) ceil($wordCount / 200));

        return $this->themeService->view('post', [
            'post'           => $post,
            'comments'       => $comments,
            'author_profile' => $authorProfile,
            'seo'            => $this->seoService->getMeta($post),
            'json_ld'        => $this->seoService->getJsonLd($post, $authorProfile),
            'preview_mode'   => true,
            'reading_time'   => $readingTime,
        ]);
    }

    protected function handleComment(object $post): bool
    {
        $captcha = new HCaptchaService();
        if (! $captcha->verify($this->request->getPost('h-captcha-response') ?? '')) {
            return false;
        }

        if (! $this->validate([
            'content' => 'required|min_length[3]|max_length[2000]',
        ])) {
            return false;
        }

        // Auto-fill author details from the logged-in user
        $userRow = db_connect()->table('users u')
            ->select('u.username, ai.secret AS email')
            ->join('auth_identities ai', 'ai.user_id = u.id AND ai.type = \'email_password\'', 'left')
            ->where('u.id', auth()->id())
            ->get()->getRowObject();

        $profileModel = new AuthorProfileModel();
        $profile      = $profileModel->getByUserId((int) auth()->id());
        $displayName  = ($profile && ! empty($profile->display_name))
            ? $profile->display_name
            : ($userRow->username ?? '');
        $authorEmail  = $userRow->email ?? '';

        $status = setting('App.commentModeration') ? 'pending' : 'approved';
        $model  = new CommentModel();
        $model->insert([
            'post_id'      => $post->id,
            'author_name'  => $displayName,
            'author_email' => $authorEmail,
            'content'      => $this->request->getPost('content'),
            'parent_id'    => $this->validatedParentId($this->request->getPost('parent_id'), $post->id),
            'user_id'      => auth()->id(),
            'status'       => $status,
        ]);

        return true;
    }

    private function logPageView(int $entityId, string $entityType): void
    {
        if ($this->request->getUserAgent()->isRobot()) {
            return;
        }

        $referer = $this->request->getServer('HTTP_REFERER') ?? '';
        $domain  = $referer ? (parse_url($referer, PHP_URL_HOST) ?: null) : null;

        try {
            (new \App\Models\PageViewModel())->insert([
                'entity_type'     => $entityType,
                'entity_id'       => $entityId,
                'referrer_domain' => $domain,
                'viewed_at'       => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // Never break page delivery due to analytics failure
            log_message('error', 'PageView log failed: ' . $e->getMessage());
        }
    }

    protected function validatedParentId(mixed $raw, int $postId): ?int
    {
        if (! $raw) {
            return null;
        }
        $parentId = (int) $raw;
        $exists   = db_connect()->table('comments')
            ->where('id', $parentId)
            ->where('post_id', $postId)
            ->countAllResults();
        return $exists ? $parentId : null;
    }

    public function category(string $slug): string
    {
        $this->buildLangSwitcher();

        $catModel = new CategoryModel();
        $category = $catModel->findBySlug($slug);
        if (! $category) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $perPage = (int) (setting('App.postsPerPage') ?? 10);
        $posts   = $this->postModel->published()
            ->byCategory($category->id)
            ->orderBy('posts.published_at', 'DESC')
            ->paginate($perPage, 'default');

        $breadcrumb = $this->seoService->getBreadcrumbJsonLd([
            ['name' => 'Home',     'url' => site_url()],
            ['name' => $category->name, 'url' => site_url('category/' . $category->slug)],
        ]);

        return $this->themeService->view('category', [
            'category' => $category,
            'posts'    => $posts,
            'pager'    => $this->postModel->pager,
            'seo'      => $this->seoService->getMeta($category),
            'json_ld'  => $breadcrumb,
        ]);
    }

    public function tag(string $slug): string
    {
        $this->buildLangSwitcher();

        $tagModel = new TagModel();
        $tag      = $tagModel->findBySlug($slug);
        if (! $tag) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $perPage = (int) (setting('App.postsPerPage') ?? 10);
        $posts   = $this->postModel->published()
            ->byTag($tag->id)
            ->orderBy('posts.published_at', 'DESC')
            ->paginate($perPage, 'default');

        $breadcrumb = $this->seoService->getBreadcrumbJsonLd([
            ['name' => 'Home', 'url' => site_url()],
            ['name' => $tag->name, 'url' => site_url('tag/' . $tag->slug)],
        ]);

        return $this->themeService->view('tag', [
            'tag'     => $tag,
            'posts'   => $posts,
            'pager'   => $this->postModel->pager,
            'seo'     => $this->seoService->getMeta($tag),
            'json_ld' => $breadcrumb,
        ]);
    }

    public function archive(int $year, int $month): string
    {
        $this->buildLangSwitcher();

        $perPage = (int) (setting('App.postsPerPage') ?? 10);
        $posts   = $this->postModel->published()
            ->where("YEAR(published_at)", $year)
            ->where("MONTH(published_at)", $month)
            ->orderBy('published_at', 'DESC')
            ->paginate($perPage);

        $archiveTitle = date('F Y', mktime(0, 0, 0, $month, 1, $year));
        $fake = (object) ['title' => $archiveTitle];
        $breadcrumb = $this->seoService->getBreadcrumbJsonLd([
            ['name' => 'Home',        'url' => site_url()],
            ['name' => $archiveTitle, 'url' => site_url("archive/{$year}/{$month}")],
        ]);

        return $this->themeService->view('archive', [
            'posts'   => $posts,
            'pager'   => $this->postModel->pager,
            'year'    => $year,
            'month'   => $month,
            'archive' => $fake,
            'seo'     => $this->seoService->getMeta($fake),
            'json_ld' => $breadcrumb,
        ]);
    }
}
