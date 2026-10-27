<?php

namespace App\Services\Identity;

use App\Models\Identity\User;
use App\Repositories\Identity\Contracts\OpdRepositoryInterface;
use App\Repositories\Identity\Contracts\UserRepositoryInterface;
use App\Repositories\System\Contracts\RoleRepositoryInterface;
use App\Services\Course\TrainerService;
use Illuminate\Support\Str;

class AdminUserService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly OpdRepositoryInterface $opds,
        private readonly RoleRepositoryInterface $roles,
        private readonly TrainerService $trainers,
    ) {}

    public function managementUsers()
    {
        return $this->users->getManagementUsers();
    }

    public function activeOpds()
    {
        return $this->opds->getActive();
    }

    public function availableRoles(): array
    {
        return $this->roles->getAdministrators()->map(fn ($role) => [
            'id' => $role->id, 'name' => $role->name, 'label' => Str::headline($role->name),
        ])->all();
    }

    public function create(array $data): User
    {
        $user = $this->users->create([
            'name' => $data['name'], 'nip' => $data['nip'] ?? null,
            'email' => $data['email'], 'password' => $data['password'] ?? Str::password(64),
            'auth_source' => $data['auth_source'], 'opd_id' => $data['opd_id'] ?? null,
        ]);
        $this->users->syncRoles($user, $data['roles']);
        if ($user->hasRole('trainer')) $this->trainers->getOrCreateForUser($user);
        return $user;
    }

    public function update(User $user, array $data): void
    {
        if ($user->usesLocalAuthentication()) {
            $this->users->update($user, array_filter([
                'name' => $data['name'], 'email' => $data['email'], 'password' => $data['password'] ?? null,
            ]));
            if (array_key_exists('opd_id', $data)) $this->users->update($user, ['opd_id' => $data['opd_id']]);
        }
        $this->users->syncRoles($user, $user->getRoleNames()->merge($data['roles'])->unique()->all());
        if ($user->hasRole('trainer')) $this->trainers->getOrCreateForUser($user);
    }

    public function delete(User $user): void
    {
        $this->users->delete($user);
    }
}
