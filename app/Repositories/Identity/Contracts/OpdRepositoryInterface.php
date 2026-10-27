<?php

namespace App\Repositories\Identity\Contracts;

use App\Models\Identity\Opd;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface OpdRepositoryInterface extends BaseRepositoryInterface
{
    /** @return Builder<Opd> */
    public function tableQuery(): Builder;

    public function lastSyncedAt(): ?string;

    public function findByCodeIncludingTrashed(string $code): ?Opd;

    public function restore(Opd $opd): void;

    public function deactivateMissingSynced(array $codes): int;

    public function getActive(): Collection;

    public function firstOrCreateByCode(string $code, array $attributes): Opd;

    public function countActiveByIds(array $ids): int;
}
