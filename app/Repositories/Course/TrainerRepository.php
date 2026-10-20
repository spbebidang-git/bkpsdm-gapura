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
}
