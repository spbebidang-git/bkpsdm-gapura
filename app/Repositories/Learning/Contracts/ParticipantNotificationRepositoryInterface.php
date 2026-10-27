<?php

namespace App\Repositories\Learning\Contracts;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ParticipantNotificationRepositoryInterface extends BaseRepositoryInterface
{
    public function unreadCount(int|string $participantId): int;
    public function latestFor(int|string $participantId, int $limit = 10): Collection;
    public function pageFor(int|string $participantId, bool $unread, int $perPage = 20, bool $enabled = true): LengthAwarePaginator;
    public function markRead(int|string $participantId, ?string $id = null): void;
}
