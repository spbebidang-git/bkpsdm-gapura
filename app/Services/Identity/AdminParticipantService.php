<?php

namespace App\Services\Identity;

use App\Models\Identity\User;
use App\Repositories\Identity\Contracts\EmployeeStatusRepositoryInterface;
use App\Repositories\Identity\Contracts\OpdRepositoryInterface;
use App\Repositories\Identity\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminParticipantService
{
    public function __construct(private readonly UserRepositoryInterface $users, private readonly EmployeeStatusRepositoryInterface $statuses, private readonly OpdRepositoryInterface $opds) {}

    public function create(array $data): void
    {
        $participant = $this->users->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'nip' => $data['nip'] ?: null,
            'password' => Hash::make($data['password']),
            'auth_source' => User::AUTH_SOURCE_LOCAL,
            'employee_status_id' => $data['employee_status_id'] ?? null,
            'opd_id' => $data['opd_id'] ?? null,
        ]);
        $this->users->assignRole($participant, 'participant');
        $this->saveProfileMeta($participant, $data);

    }

    public function update(User $participant, array $data): void
    {
        $attributes = [
            'name' => $data['name'],
            'email' => $data['email'],
            'nip' => $data['nip'] ?: null,
            'employee_status_id' => $data['employee_status_id'] ?? null,
            'opd_id' => $data['opd_id'] ?? null,
        ];
        if (filled($data['password'] ?? null)) {
            $attributes['password'] = Hash::make($data['password']);
        }
        $this->users->update($participant, $attributes);
        $this->saveProfileMeta($participant, $data);

    }

    public function delete(User $participant): void
    {
        DB::transaction(function () use ($participant) {
            $this->users->removeRole($participant, 'participant');
            if (! $this->users->hasRoles($participant)) {
                $this->users->delete($participant);
            }
        });
    }

    public function load(User $participant): User
    {
        return $this->users->loadParticipant($participant);
    }

    public function orderMeta($query, string $key, string $order): void
    {
        $this->users->orderParticipantMeta($query, $key, $order);
    }

    public function options(array $props): array
    {
        return [...$props, 'employeeStatuses' => $this->statuses->getAllOrderedByTitle()->map->only(['id', 'title']), 'opds' => $this->opds->options(true, ['id', 'name', 'code'])];
    }

    private function saveProfileMeta(User $participant, array $data): void
    {
        $statusTitle = isset($data['employee_status_id']) ? $this->statuses->find($data['employee_status_id'])?->title : '';
        foreach (['status_pegawai' => strtoupper(trim($statusTitle ?? '')), 'nama_jabatan' => trim($data['jabatan'] ?? '')] as $key => $value) {
            if ($value === '') {
                $this->users->deleteMeta($participant, $key);
            } else {
                $this->users->putMeta($participant, $key, $value);
            }
        }
    }
}
