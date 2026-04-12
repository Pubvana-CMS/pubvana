<?php

namespace App\Controllers;

use App\Models\AuthorProfileModel;
use CodeIgniter\Shield\Authentication\Actions\EmailActivator;
use CodeIgniter\Shield\Models\UserModel;

class Account extends BaseController
{
    /**
     * GET /accounts/profile — show profile form.
     */
    public function profile(): string
    {
        $this->buildLangSwitcher();

        $user    = auth()->user();
        $groups  = $user->getGroups();
        $isAuthor = $this->isAuthorOrAbove($groups);

        $data = [
            'user'       => $user,
            'email'      => $this->getUserEmail($user->id),
            'profile'    => null,
            'is_author'  => $isAuthor,
            'seo'        => ['title' => lang('Blog.profileTitle')],
        ];

        if ($isAuthor) {
            $data['profile'] = (new AuthorProfileModel())->getByUserId($user->id);
        }

        return $this->themeService->view('profile', $data);
    }

    /**
     * POST /accounts/profile — save profile changes.
     */
    public function updateProfile(): \CodeIgniter\HTTP\RedirectResponse
    {
        $user      = auth()->user();
        $groups    = $user->getGroups();
        $isAuthor  = $this->isAuthorOrAbove($groups);
        $userModel = new UserModel();

        // Capture current values before any changes
        $oldUsername = $user->username;
        $oldEmail   = $this->getUserEmail($user->id);

        $username = trim($this->request->getPost('username') ?? '');
        $email    = trim($this->request->getPost('email') ?? '');
        $password = $this->request->getPost('password');
        $passConf = $this->request->getPost('password_confirm');

        $errors       = [];
        $needsLogout  = false;

        // --- Username ---
        if ($username === '') {
            $errors[] = lang('Blog.profileUsernameRequired');
        } elseif ($username !== $oldUsername) {
            $existing = $userModel->where('username', $username)
                ->where('id !=', $user->id)
                ->first();
            if ($existing) {
                $errors[] = lang('Blog.profileUsernameTaken');
            } else {
                $user->fill(['username' => $username]);
                $userModel->save($user);
                $this->notifyUser($oldEmail, lang('Blog.profileUsernameChangedSubject'), lang('Blog.profileUsernameChangedBody', [$oldUsername, $username]));
                $needsLogout = true;
            }
        }

        // --- Email ---
        if ($email === '') {
            $errors[] = lang('Blog.profileEmailRequired');
        } elseif ($email !== $oldEmail) {
            $existingUser = auth()->getProvider()->findByCredentials(['email' => $email]);
            $dup = ($existingUser && $existingUser->id !== $user->id) ? 1 : 0;
            if ($dup > 0) {
                $errors[] = lang('Blog.profileEmailTaken');
            } else {
                // Update to new email
                $userProvider = auth()->getProvider();
                $user->setEmail($email);
                $userProvider->save($user);

                // Notify old email
                $this->notifyUser($oldEmail, lang('Blog.profileEmailChangedSubject'), lang('Blog.profileEmailChangedBody', [$oldEmail, $email]));

                // Deactivate and send verification to new email
                $user->deactivate();
                $activator = new EmailActivator();
                $code = $activator->createIdentity($user);

                helper('email');
                $verifyEmail = emailer(['mailType' => 'html'])
                    ->setFrom(setting('Email.fromAddress') ?: 'no-reply@example.com', setting('Email.fromName') ?? '');
                $verifyEmail->setTo($email);
                $verifyEmail->setSubject(lang('Auth.emailActivateSubject'));
                $verifyEmail->setMessage(view(
                    setting('Auth.views')['action_email_activate_email'],
                    [
                        'code'      => $code,
                        'user'      => $user,
                        'ipAddress' => $this->request->getIPAddress(),
                        'userAgent' => (string) $this->request->getUserAgent(),
                        'date'      => \CodeIgniter\I18n\Time::now()->toDateTimeString(),
                    ]
                ));
                $verifyEmail->send(false);

                $needsLogout = true;
            }
        }

        // --- Password (optional) ---
        if ($password) {
            if ($password !== $passConf) {
                $errors[] = lang('Blog.profilePasswordMismatch');
            } elseif (strlen($password) < 8) {
                $errors[] = lang('Blog.profilePasswordTooShort');
            } else {
                $user->fill(['password' => $password]);
                $userModel->save($user);
                $this->notifyUser($oldEmail, lang('Blog.profilePasswordChangedSubject'), lang('Blog.profilePasswordChangedBody'));
                $needsLogout = true;
            }
        }

        // --- Author profile fields (author+) ---
        if ($isAuthor) {
            $profileData = [
                'display_name' => trim($this->request->getPost('display_name') ?? ''),
                'bio'          => trim($this->request->getPost('bio') ?? ''),
                'website'      => trim($this->request->getPost('website') ?? ''),
                'twitter'      => trim($this->request->getPost('twitter') ?? ''),
                'facebook'     => trim($this->request->getPost('facebook') ?? ''),
                'linkedin'     => trim($this->request->getPost('linkedin') ?? ''),
            ];
            (new AuthorProfileModel())->upsert($user->id, $profileData);
        }

        if (! empty($errors)) {
            return redirect()->back()->with('error', implode(' ', $errors));
        }

        if ($needsLogout) {
            auth()->logout();
            return redirect()->to('login')->with('message', lang('Blog.profileUpdatedRelogin'));
        }

        return redirect()->to('accounts/profile')->with('success', lang('Blog.profileUpdated'));
    }

    /**
     * POST /accounts/avatar — upload avatar image.
     */
    public function uploadAvatar(): \CodeIgniter\HTTP\RedirectResponse
    {
        $user   = auth()->user();
        $groups = $user->getGroups();

        if (! $this->isAuthorOrAbove($groups)) {
            return redirect()->to('accounts/profile')->with('error', lang('Blog.profileAvatarNotAllowed'));
        }

        $file = $this->request->getFile('avatar');

        if (! $file || ! $file->isValid()) {
            return redirect()->back()->with('error', lang('Blog.profileAvatarInvalid'));
        }

        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (! in_array($file->getMimeType(), $allowed, true)) {
            return redirect()->back()->with('error', lang('Blog.profileAvatarTypeError'));
        }

        if ($file->getSizeByUnit('kb') > 2048) {
            return redirect()->back()->with('error', lang('Blog.profileAvatarTooLarge'));
        }

        $avatarDir = FCPATH . 'uploads/avatars';
        if (! is_dir($avatarDir)) {
            mkdir($avatarDir, 0755, true);
        }

        // Resize to 300x300 max
        $name = 'avatar_' . $user->id . '_' . bin2hex(random_bytes(4));
        $ext  = $file->getExtension();
        $file->move($avatarDir, $name . '.' . $ext);

        $movedPath = $avatarDir . '/' . $name . '.' . $ext;

        try {
            \Config\Services::image('gd')
                ->withFile($movedPath)
                ->resize(300, 300, true, 'auto')
                ->save($movedPath);
        } catch (\Throwable $e) {
            log_message('error', 'Avatar resize failed: ' . $e->getMessage());
        }

        // Remove old avatar file if exists
        $profileModel = new AuthorProfileModel();
        $profile = $profileModel->getByUserId($user->id);
        if ($profile && ! empty($profile->avatar)) {
            $oldPath = FCPATH . ltrim($profile->avatar, '/');
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        $relPath = 'uploads/avatars/' . $name . '.' . $ext;
        $profileModel->upsert($user->id, ['avatar' => $relPath]);

        return redirect()->to('accounts/profile')->with('success', lang('Blog.profileAvatarUpdated'));
    }

    // -----------------------------------------------------------------

    private function isAuthorOrAbove(array $groups): bool
    {
        $authorGroups = ['author', 'editor', 'admin', 'superadmin'];
        foreach ($groups as $g) {
            if (in_array($g, $authorGroups, true)) {
                return true;
            }
        }
        return false;
    }

    private function getUserEmail(int $userId): string
    {
        $user = auth()->getProvider()->findById($userId);
        return $user ? $user->getEmail() : '';
    }

    private function notifyUser(string $toEmail, string $subject, string $body): void
    {
        try {
            $email = \Config\Services::email();
            $email->setFrom(setting('Email.fromAddress') ?? 'no-reply@example.com', setting('Email.fromName') ?? site_name());
            $email->setTo($toEmail);
            $email->setSubject($subject);
            $email->setMessage($body);
            $email->send();
        } catch (\Throwable $e) {
            log_message('error', 'Account notification email failed: ' . $e->getMessage());
        }
    }
}
