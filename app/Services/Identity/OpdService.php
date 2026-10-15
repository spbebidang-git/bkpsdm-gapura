<?php

namespace App\Services\Identity;

use App\Models\Identity\Opd;
use App\Repositories\Identity\Contracts\OpdRepositoryInterface;
use App\Services\Integration\OpdSyncService;
use Illuminate\Database\Eloquent\Builder;

class OpdService
{
    public function __construct(
        private readonly OpdRepositoryInterface $opds,
        private readonly OpdSyncService $sync,
    ) {}

    /** @return Builder<Opd> */
    public function getOpdsForTable(): Builder
    {
        return $this->opds->tableQuery();
    }

    public function getIndexData(): array
    {
        return [
            'lastSyncedAt' => $this->opds->lastSyncedAt(),
            'configured' => filled(config('services.kedaton.opd_master_path')),
        ];
    }

    public function createOpd(array $data): Opd
    {
        return $this->opds->create(array_merge($data, ['synced_at' => null]));
    }

    public function updateOpd(Opd $opd, array $data): Opd
    {
        // Manual edits become locally managed until the next Kedaton sync.
        return $this->opds->update($opd, array_merge($data, ['synced_at' => null]));
    }

    public function deleteOpd(Opd $opd): void
    {
        $this->opds->delete($opd);
    }

    /** @return array{created:int,updated:int,restored:int,deactivated:int,message:string} */
    public function syncOpds(): array
    {
        return $this->sync->sync();
    }
}
