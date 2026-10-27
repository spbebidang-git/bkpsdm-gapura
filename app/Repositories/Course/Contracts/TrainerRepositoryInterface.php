<?php

namespace App\Repositories\Course\Contracts;

use App\Models\Course\Trainer;
use App\Models\Identity\User;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface TrainerRepositoryInterface extends BaseRepositoryInterface
{
    public function getLatestForAdmin(): Collection;

    public function getAllOrderedByName(): Collection;

    public function getOrCreateForUser(User $user): Trainer;

    public function existsByUser(int|string $userId): bool;

    public function lock(int|string $id): Trainer;

    public function syncOpds(Trainer $trainer, array $ids, bool $detach = true): void;
}
