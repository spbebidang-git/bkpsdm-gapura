<?php

namespace App\Repositories\Identity;

use App\Models\Identity\Opd;
use App\Repositories\EloquentRepository;
use App\Repositories\Identity\Contracts\OpdRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class OpdRepository extends EloquentRepository implements OpdRepositoryInterface
{
    public function __construct(Opd $model)
    {
        parent::__construct($model);
    }

    public function tableQuery(): Builder
    {
        return $this->query()->orderBy('name');
    }

    public function lastSyncedAt(): ?string
    {
        return $this->query()->max('synced_at');
    }

    public function findByCodeIncludingTrashed(string $code): ?Opd
    {
        return $this->query()->withTrashed()->where('code', $code)->first();
    }

    public function restore(Opd $opd): void
    {
        $opd->restore();
    }

    public function deactivateMissingSynced(array $codes): int
    {
        if ($codes === []) {
            return 0;
        }

        return $this->query()->whereNotNull('synced_at')->whereNotIn('code', $codes)
            ->where('is_active', true)->update(['is_active' => false]);
    }
}
