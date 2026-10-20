<?php

namespace App\Services\Course;

use App\Helper\UploadMedia;
use App\Models\Course\Trainer;
use App\Models\Identity\User;
use App\Repositories\Course\Contracts\TrainerRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TrainerService
{
    public function __construct(private readonly TrainerRepositoryInterface $trainers) {}

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
        User::role('trainer')->whereNotIn('id', Trainer::query()->whereNotNull('user_id')->select('user_id'))
            ->each(function (User $user) {
                $this->getOrCreateForUser($user);
            });
    }

    public function getEligibleUsers(): Collection
    {
        return User::query()
            ->where(fn ($query) => $query
                ->where('auth_source', User::AUTH_SOURCE_LOCAL)
                ->orWhereHas('opd', fn ($opd) => $opd->where('is_active', true)))
            ->whereNotIn('id', Trainer::query()->whereNotNull('user_id')->select('user_id'))
            ->whereDoesntHave('roles', fn ($query) => $query->whereIn('name', ['trainer', 'super_admin']))
            ->with(['opd', 'metas'])->orderBy('name')->get()
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
        if ($user->opd_id) $trainer->opds()->syncWithoutDetaching([$user->opd_id]);
        return $trainer;
    }

    public function create(array $attributes): mixed
    {
        $source = $attributes['source'];
        unset($attributes['source']);
        if ($source === 'user') {
            return DB::transaction(function () use ($attributes) {
                $user = User::whereKey($attributes['user_id'])->lockForUpdate()->first();
                $opdIds = collect($attributes['opd_ids'] ?? [])->map(fn ($id) => (int) $id)->unique();
                if ($user?->opd_id) $opdIds->push((int) $user->opd_id);
                $opdIds = $opdIds->unique()->values();
                $opdIsActive = $opdIds->isNotEmpty() && \App\Models\Identity\Opd::query()->whereIn('id', $opdIds)->where('is_active', true)->count() === $opdIds->count();
                if (! $user || ! $opdIsActive || $user->hasAnyRole(['trainer', 'super_admin'])
                    || Trainer::where('user_id', $user->id)->exists()) {
                    throw ValidationException::withMessages(['user_id' => 'Pilih pengguna dengan OPD aktif yang belum terdaftar sebagai trainer.']);
                }
                if ($user->usesLocalAuthentication() && (string) $user->opd_id !== (string) $opdIds->first()) {
                    $user->update(['opd_id' => $opdIds->first()]);
                }
                $trainer = $this->getOrCreateForUser($user);
                $user->assignRole('trainer');
                $trainer->opds()->sync($opdIds->all());
                return $trainer;
            });
        }
        if (isset($attributes['email'])) {
            return DB::transaction(function () use ($attributes) {
                $user = User::create([
                    'name' => $attributes['name'],
                    'email' => $attributes['email'],
                    'password' => $attributes['password'],
                    'opd_id' => $attributes['opd_ids'][0],
                    'auth_source' => User::AUTH_SOURCE_LOCAL,
                ]);
                $user->assignRole('trainer');
                $user->metas()->create(['key' => 'nama_jabatan', 'value' => $attributes['jabatan'] ?? '']);
                if (($attributes['photo'] ?? null) instanceof UploadedFile) {
                    $user->update(['image' => UploadMedia::file($attributes['photo'], User::$directoryImage)]);
                }
                $trainer = $this->getOrCreateForUser($user);
                $trainer->opds()->sync($attributes['opd_ids']);
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
                $user = User::whereKey($trainer->user_id)->lockForUpdate()->firstOrFail();
                $user->update([
                    'name' => $attributes['name'],
                    'opd_id' => $attributes['opd_ids'][0],
                    ...($user->usesLocalAuthentication() ? array_filter([
                        'email' => $attributes['email'] ?? null,
                        'password' => $attributes['password'] ?? null,
                    ], fn ($value) => $value !== null && $value !== '') : []),
                ]);

                $user->metas()->updateOrCreate(
                    ['key' => 'nama_jabatan'],
                    ['value' => $attributes['jabatan'] ?? ''],
                );
                $trainer->opds()->sync($attributes['opd_ids']);

                if (($attributes['photo'] ?? null) instanceof UploadedFile) {
                    if ($user->image && is_file_exists(User::$directoryImage.$user->image)) {
                        remove_file(User::$directoryImage.$user->image);
                    }
                    $user->update(['image' => UploadMedia::file($attributes['photo'], User::$directoryImage)]);
                }

                return $trainer->fresh(['user.opd', 'user.metas']);
            });
        }

        if (isset($attributes['email'])) {
            return DB::transaction(function () use ($trainer, $attributes) {
                $user = User::create([
                    'name' => $attributes['name'],
                    'email' => $attributes['email'],
                    'password' => $attributes['password'],
                    'opd_id' => $attributes['opd_ids'][0],
                    'auth_source' => User::AUTH_SOURCE_LOCAL,
                ]);
                $user->assignRole('trainer');
                $user->metas()->create(['key' => 'nama_jabatan', 'value' => $attributes['jabatan'] ?? '']);
                if (($attributes['photo'] ?? null) instanceof UploadedFile) {
                    $user->update(['image' => UploadMedia::file($attributes['photo'], User::$directoryImage)]);
                } elseif ($trainer->photo && is_file_exists(Trainer::$directoryImage.$trainer->photo)) {
                    \Illuminate\Support\Facades\File::ensureDirectoryExists(public_path(User::$directoryImage));
                    $filename = $trainer->getKey().'_'.basename($trainer->photo);
                    \Illuminate\Support\Facades\File::copy(public_path(Trainer::$directoryImage.$trainer->photo), public_path(User::$directoryImage.$filename));
                    $user->update(['image' => $filename]);
                }
                $trainer->update(['user_id' => $user->id]);
                $trainer->opds()->sync($attributes['opd_ids']);
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
            $trainer = Trainer::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($trainer->user_id) {
                $user = User::whereKey($trainer->user_id)->lockForUpdate()->first();
                $user?->removeRole('trainer');
            }
            return $this->trainers->delete($trainer);
        });
    }
}
