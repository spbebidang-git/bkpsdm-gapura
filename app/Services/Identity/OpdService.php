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
            'configured' => filled(config('services.kedaton.opd_master_path')) && filled(config('services.kedaton.data_key')),
        ];
    }

    /** @return array{created:int,updated:int,restored:int,deactivated:int,message:string} */
    public function syncOpds(): array
    {
        return $this->sync->sync();
    }
}
