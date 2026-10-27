<?php

namespace App\Repositories\Learning;

use App\Models\Learning\ParticipantNotification;
use App\Repositories\EloquentRepository;
use App\Repositories\Learning\Contracts\ParticipantNotificationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ParticipantNotificationRepository extends EloquentRepository implements ParticipantNotificationRepositoryInterface
{
    public function __construct(ParticipantNotification $model) { parent::__construct($model); }

    public function unreadCount(int|string $participantId): int
    {
        return $this->query()->where('participant_id', $participantId)->whereNull('read_at')->count();
    }

    public function latestFor(int|string $participantId, int $limit = 10): Collection
    {
        return $this->query()->where('participant_id', $participantId)->latest()->limit($limit)->get();
    }

    public function pageFor(int|string $participantId, bool $unread, int $perPage = 20, bool $enabled = true): LengthAwarePaginator
    {
        return $this->query()->where('participant_id', $participantId)->latest()
            ->when($unread, fn ($query) => $query->whereNull('read_at'))
            ->when(! $enabled, fn ($query) => $query->whereRaw('1 = 0'))
            ->paginate($perPage)->withQueryString();
    }

    public function markRead(int|string $participantId, ?string $id = null): void
    {
        $this->query()->where('participant_id', $participantId)->whereNull('read_at')
            ->when($id, fn ($query) => $query->whereKey($id))->update(['read_at' => now()]);
    }
}
