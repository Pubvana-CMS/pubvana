<?php

namespace App\Controllers\Admin;

use App\Models\AuthorProfileModel;
use App\Models\UserAdminModel;
use App\Services\ActivityLogger;
use App\Services\MediaService;
use CodeIgniter\Shield\Models\UserModel;

class Users extends BaseAdminController
{
    public function index(): string
    {
        if (! auth()->user()->can('users.manage')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }

        $users = auth()->getProvider()
            ->withIdentities()
            ->withGroups()
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $filter = $this->request->getGet('filter');
        if ($filter === 'banned') {
            $users = array_filter($users, fn($u) => $u->isBanned());
        }

        return $this->adminView('users/index', array_merge($this->baseData('Users', 'users'), [
            'users'  => $users,
            'filter' => $filter ?? '',
        ]));
    }

    public function edit(int $id): string
    {
        $userModel = new UserModel();
        $user      = $userModel->findById($id);
        if (! $user) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $groups       = config('AuthGroups')->groups;
        $currentGroup = $user->getGroups()[0] ?? '';

        return $this->adminView('users/edit', array_merge($this->baseData('Edit User', 'users'), [
            'subject_user' => $user,
            'groups'       => $groups,
            'current_group'=> $currentGroup,
        ]));
    }

    public function update(int $id)
    {
        if (! auth()->user()->can('users.manage')) {
            return redirect()->to('/admin/users')->with('error', lang('Admin.permissionDenied'));
        }

        // Protect site owner (lowest ID)
        $ownerId = model(\App\Models\UserAdminModel::class)->getOwnerId();
        if ($id === $ownerId && auth()->id() !== $ownerId) {
            return redirect()->to('/admin/users')->with('error', lang('Admin.userOwnerCannotModify'));
        }

        $userModel = new UserModel();
        $user      = $userModel->findById($id);
        if (! $user) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // Role
        $role = $this->request->getPost('role');
        if ($role && array_key_exists($role, config('AuthGroups')->groups)) {
            foreach ($user->getGroups() as $g) {
                $user->removeGroup($g);
            }
            $user->addGroup($role);
        }

        // Active status
        if ($this->request->getPost('active')) {
            $user->activate();
        } elseif ($id !== auth()->id() && $id !== $ownerId) {
            $user->deactivate();
            model(\CodeIgniter\Shield\Models\RememberModel::class)->purgeRememberTokens($user);
        }

        // Password (optional)
        $password = $this->request->getPost('password');
        if ($password) {
            $user->fill(['password' => $password]);
            $userModel->save($user);
        }

        ActivityLogger::log('user.updated', 'user', $id, 'Updated user: ' . ($user->username ?? $id));
        return redirect()->to('/admin/users/' . $id . '/edit')->with('success', lang('Admin.userUpdated'));
    }

    public function delete(int $id)
    {
        if ($id === auth()->id()) {
            return redirect()->to('/admin/users')->with('error', lang('Admin.userCannotDeleteSelf'));
        }
        $ownerId = model(\App\Models\UserAdminModel::class)->getOwnerId();
        if ($id === $ownerId) {
            return redirect()->to('/admin/users')->with('error', lang('Admin.userCannotDeleteOwner'));
        }
        (new UserModel())->delete($id, true);
        ActivityLogger::log('user.deleted', 'user', $id, 'Deleted user ID: ' . $id);
        return redirect()->to('/admin/users')->with('success', lang('Admin.userDeleted'));
    }

    public function toggleBan(int $id)
    {
        if (! auth()->user()->can('users.manage')) {
            return redirect()->to('/admin/users')->with('error', lang('Admin.permissionDenied'));
        }

        $ownerId = model(\App\Models\UserAdminModel::class)->getOwnerId();
        if ($id === auth()->id() || $id === $ownerId) {
            return redirect()->to('/admin/users/' . $id . '/edit')->with('error', lang('Admin.userCannotBanSelf'));
        }

        $users = auth()->getProvider();
        $user  = $users->findById($id);
        if (! $user) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if ($user->isBanned()) {
            $user->unBan();
            ActivityLogger::log('user.unbanned', 'user', $id, 'Unbanned user: ' . ($user->username ?? $id));
            return redirect()->to('/admin/users/' . $id . '/edit')->with('success', lang('Admin.userUnbanned'));
        }

        $reason = $this->request->getPost('ban_reason');
        $user->ban($reason ? trim($reason) : null);
        model(\CodeIgniter\Shield\Models\RememberModel::class)->purgeRememberTokens($user);
        ActivityLogger::log('user.banned', 'user', $id, 'Banned user: ' . ($user->username ?? $id) . ' — ' . trim($reason));
        return redirect()->to('/admin/users/' . $id . '/edit')->with('success', lang('Admin.userBanned'));
    }

    public function create(): string
    {
        if (! auth()->user()->can('users.manage')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }
        return $this->adminView('users/create', $this->baseData('Create User', 'users'));
    }

    public function store()
    {
        if (! auth()->user()->can('users.manage')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }
        if (! $this->validate([
            'username' => 'required|min_length[3]|max_length[30]|is_unique[users.username]',
            'email'    => 'required|valid_email',
            'password' => 'required|min_length[8]',
            'role'     => 'required|in_list[subscriber,author,editor,admin,superadmin]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $users = auth()->getProvider();
        $user  = new \CodeIgniter\Shield\Entities\User([
            'username' => $this->request->getPost('username'),
            'active'   => 1,
        ]);
        $user->setEmail($this->request->getPost('email'));
        $user->setPassword($this->request->getPost('password'));
        $users->save($user);

        $newUser = $users->findByCredentials(['email' => $this->request->getPost('email')]);
        if ($newUser) {
            $newUser->addGroup($this->request->getPost('role'));
        }

        ActivityLogger::log('user.created', 'user', null, 'Created user: ' . $this->request->getPost('username'));
        return redirect()->to('/admin/users')->with('success', lang('Admin.userCreated'));
    }

    public function profile(int $id): string
    {
        if ($id !== auth()->id() && ! auth()->user()->can('users.manage')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }

        $userModel = new UserModel();
        $user      = $userModel->findById($id);
        if (! $user) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $profileModel = new AuthorProfileModel();
        $profile      = $profileModel->getByUserId($id) ?? (object) [];

        return $this->adminView('users/profile', array_merge($this->baseData('Author Profile', 'users'), [
            'subject_user'  => $user,
            'profile'       => $profile,
            'totp_enabled'  => (auth()->loggedIn() && auth()->id() === $id)
                               ? model(UserAdminModel::class)->isTotpEnabled($id)
                               : false,
        ]));
    }

    public function saveProfile(int $id)
    {
        if ($id !== auth()->id() && ! auth()->user()->can('users.manage')) {
            return redirect()->to('/admin/users/' . $id . '/profile')->with('error', lang('Admin.permissionDenied'));
        }

        $userModel = new UserModel();
        $user      = $userModel->findById($id);
        if (! $user) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $data = [
            'display_name' => $this->request->getPost('display_name'),
            'bio'          => $this->request->getPost('bio'),
            'website'      => $this->request->getPost('website'),
            'twitter'      => ltrim($this->request->getPost('twitter') ?? '', '@'),
            'facebook'     => $this->request->getPost('facebook'),
            'linkedin'     => $this->request->getPost('linkedin'),
        ];

        // Handle avatar upload
        $avatar = $this->request->getFile('avatar');
        if ($avatar && $avatar->isValid() && ! $avatar->hasMoved()) {
            try {
                $mediaService  = new MediaService();
                $result        = $mediaService->upload($avatar, auth()->id());
                $data['avatar'] = $result['path'];
            } catch (\RuntimeException $e) {
                return redirect()->back()->with('error', lang('Admin.userAvatarUploadFail', [$e->getMessage()]));
            }
        }

        $profileModel = new AuthorProfileModel();
        $profileModel->upsert($id, $data);

        return redirect()->to('/admin/users/' . $id . '/profile')->with('success', lang('Admin.userProfileSaved'));
    }
}
