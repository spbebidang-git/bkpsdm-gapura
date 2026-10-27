<?php

namespace App\Repositories\Course;

use App\Models\Course\Trainer;
use App\Models\Identity\User;
use App\Repositories\Course\Contracts\TrainerRepositoryInterface;
use App\Repositories\EloquentRepository;
use Illuminate\Database\Eloquent\Collection;

class TrainerRepository extends EloquentRepository implements TrainerRepositoryInterface
{
    public function __construct(Trainer $model)
    {
        parent::__construct($model);
    }

    public function getLatestForAdmin(): Collection
    {
        return $this->query()->with(['user.opd', 'user.metas', 'opd', 'opds'])->orderByDesc('id')->get();
    }

    public function getAllOrderedByName(): Collection
    {
        return $this->query()->with(['user.opd', 'user.metas', 'opd', 'opds'])->orderBy('name')->get();
    }

    public function getOrCreateForUser(User $user): Trainer
    {
        $trainer = Trainer::withTrashed()->firstOrCreate(
            ['user_id' => $user->id],
            ['name' => $user->name, 'jabatan' => 'Trainer'],
        );

        if ($trainer->trashed()) {
            $trainer->restore();
        }

        return $trainer;
    }

    public function existsByUser(int|string $userId): bool
    {
        return $this->query()->where('user_id', $userId)->exists();
    }

    public function lock(int|string $id): Trainer
    {
        return $this->query()->whereKey($id)->lockForUpdate()->firstOrFail();
    }

    public function syncOpds(Trainer $trainer, array $ids, bool $detach = true): void
    {
        if ($detach) $trainer->opds()->sync($ids);
        else $trainer->opds()->syncWithoutDetaching($ids);
    }
}
