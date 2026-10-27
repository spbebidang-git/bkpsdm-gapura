<?php

namespace App\Services\Course;

use App\Helper\UploadMedia;
use App\Models\Course\Trainer;
use App\Models\Identity\User;
use App\Repositories\Course\Contracts\TrainerRepositoryInterface;
use App\Repositories\Identity\Contracts\UserRepositoryInterface;
use App\Repositories\Identity\Contracts\OpdRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TrainerService
{
    public function __construct(
        private readonly TrainerRepositoryInterface $trainers,
        private readonly UserRepositoryInterface $users,
        private readonly OpdRepositoryInterface $opds,
    ) {}

    public function getForAdmin(): Collection
    {
        $this->syncUsersWithTrainerRole();
        return $this->trainers->getLatestForAdmin();
    }

    /** Daftar trainer untuk form (select/checkbox). */
    public function getForForm(): Collection
    {
        $this->syncUsersWithTrainerRole();
        return $this->trainers->getAllOrderedByName();
    }

    private function syncUsersWithTrainerRole(): void
    {
        $this->users->trainersWithoutProfile()->each(function (User $user) {
                $this->getOrCreateForUser($user);
            });
    }

    public function getEligibleUsers(): Collection
    {
        return $this->users->eligibleTrainerUsers()
            ->map(fn (User $user) => $this->userProfile($user));
    }

    public function userProfile(User $user): array
    {
        return [
            'id' => $user->id, 'name' => $user->name, 'nip' => $user->nip,
            'jabatan' => $user->metas->firstWhere('key', 'nama_jabatan')?->value,
            'photo_src' => $user->profile_picture, 'opd' => $user->opd?->name,
            'auth_source' => $user->auth_source,
            'source_label' => $user->usesSsoAuthentication() ? 'Kedaton SSO' : ($user->usesPrestigeAuthentication() ? 'Prestige' : 'GAPURA lokal'),
            'opd_id' => $user->opd_id,
        ];
    }

    public function getOrCreateForUser(User $user): Trainer
    {
        $trainer = $this->trainers->getOrCreateForUser($user);
        if ($user->opd_id) $this->trainers->syncOpds($trainer, [$user->opd_id], false);
        return $trainer;
    }

    public function create(array $attributes): mixed
    {
        $source = $attributes['source'];
        unset($attributes['source']);
        if ($source === 'user') {
            return DB::transaction(function () use ($attributes) {
                $user = $this->users->lock($attributes['user_id']);
                $opdIds = collect($attributes['opd_ids'] ?? [])->map(fn ($id) => (int) $id)->unique();
                if ($user?->opd_id) $opdIds->push((int) $user->opd_id);
                $opdIds = $opdIds->unique()->values();
                $opdIsActive = $opdIds->isNotEmpty() && $this->opds->countActiveByIds($opdIds->all()) === $opdIds->count();
                if (! $user || ! $opdIsActive || $user->hasAnyRole(['trainer', 'super_admin'])
                    || $this->trainers->existsByUser($user->id)) {
                    throw ValidationException::withMessages(['user_id' => 'Pilih pengguna dengan OPD aktif yang belum terdaftar sebagai pemateri.']);
                }
                if ($user->usesLocalAuthentication() && (string) $user->opd_id !== (string) $opdIds->first()) {
                    $this->users->update($user, ['opd_id' => $opdIds->first()]);
                }
                $trainer = $this->getOrCreateForUser($user);
                $this->users->assignRole($user, 'trainer');
                $this->trainers->syncOpds($trainer, $opdIds->all());
                return $trainer;
            });
        }
        if (isset($attributes['email'])) {
            return DB::transaction(function () use ($attributes) {
                $user = $this->users->create([
                    'name' => $attributes['name'],
                    'email' => $attributes['email'],
                    'password' => $attributes['password'],
                    'opd_id' => $attributes['opd_ids'][0],
                    'auth_source' => User::AUTH_SOURCE_LOCAL,
                ]);
                $this->users->assignRole($user, 'trainer');
                $this->users->putMeta($user, 'nama_jabatan', $attributes['jabatan'] ?? '');
                if (($attributes['photo'] ?? null) instanceof UploadedFile) {
                    $this->users->update($user, ['image' => UploadMedia::file($attributes['photo'], User::$directoryImage)]);
                }
                $trainer = $this->getOrCreateForUser($user);
                $this->trainers->syncOpds($trainer, $attributes['opd_ids']);
                return $trainer;
            });
        }
        unset($attributes['user_id']);
        if (($attributes['photo'] ?? null) instanceof UploadedFile) {
            $attributes['photo'] = UploadMedia::file($attributes['photo'], Trainer::$directoryImage);
        } else {
            unset($attributes['photo']);
        }

        return $this->trainers->create($attributes);
    }

    public function update(mixed $trainer, array $attributes): mixed
    {
        if ($trainer->user_id) {
            return DB::transaction(function () use ($trainer, $attributes) {
                $user = $this->users->lock($trainer->user_id) ?? abort(404);
                $this->users->update($user, [
                    'name' => $attributes['name'],
                    'opd_id' => $attributes['opd_ids'][0],
                    ...($user->usesLocalAuthentication() ? array_filter([
                        'email' => $attributes['email'] ?? null,
                        'password' => $attributes['password'] ?? null,
                    ], fn ($value) => $value !== null && $value !== '') : []),
                ]);

                $this->users->putMeta($user, 'nama_jabatan', $attributes['jabatan'] ?? '');
                $this->trainers->syncOpds($trainer, $attributes['opd_ids']);

                if (($attributes['photo'] ?? null) instanceof UploadedFile) {
                    if ($user->image && is_file_exists(User::$directoryImage.$user->image)) {
                        remove_file(User::$directoryImage.$user->image);
                    }
                    $this->users->update($user, ['image' => UploadMedia::file($attributes['photo'], User::$directoryImage)]);
                }

                return $trainer->fresh(['user.opd', 'user.metas']);
            });
        }

        if (isset($attributes['email'])) {
            return DB::transaction(function () use ($trainer, $attributes) {
                $user = $this->users->create([
                    'name' => $attributes['name'],
                    'email' => $attributes['email'],
                    'password' => $attributes['password'],
                    'opd_id' => $attributes['opd_ids'][0],
                    'auth_source' => User::AUTH_SOURCE_LOCAL,
                ]);
                $this->users->assignRole($user, 'trainer');
                $this->users->putMeta($user, 'nama_jabatan', $attributes['jabatan'] ?? '');
                if (($attributes['photo'] ?? null) instanceof UploadedFile) {
                    $this->users->update($user, ['image' => UploadMedia::file($attributes['photo'], User::$directoryImage)]);
                } elseif ($trainer->photo && is_file_exists(Trainer::$directoryImage.$trainer->photo)) {
                    \Illuminate\Support\Facades\File::ensureDirectoryExists(public_path(User::$directoryImage));
                    $filename = $trainer->getKey().'_'.basename($trainer->photo);
                    \Illuminate\Support\Facades\File::copy(public_path(Trainer::$directoryImage.$trainer->photo), public_path(User::$directoryImage.$filename));
                    $this->users->update($user, ['image' => $filename]);
                }
                $this->trainers->update($trainer, ['user_id' => $user->id]);
                $this->trainers->syncOpds($trainer, $attributes['opd_ids']);
                return $trainer->fresh(['user']);
            });
        }

        unset($attributes['source'], $attributes['user_id']);
        if (($attributes['photo'] ?? null) instanceof UploadedFile) {
            if ($trainer->photo && is_file_exists(Trainer::$directoryImage.$trainer->photo)) {
                remove_file(Trainer::$directoryImage.$trainer->photo);
            }

            $attributes['photo'] = UploadMedia::file($attributes['photo'], Trainer::$directoryImage);
        } else {
            unset($attributes['photo']);
        }

        return $this->trainers->update($trainer, $attributes);
    }

    public function delete($id): bool
    {
        return DB::transaction(function () use ($id) {
            $trainer = $this->trainers->lock($id);
            if ($trainer->user_id) {
                $user = $this->users->lock($trainer->user_id);
                if ($user) $this->users->removeRole($user, 'trainer');
            }
            return $this->trainers->delete($trainer);
        });
    }
}
