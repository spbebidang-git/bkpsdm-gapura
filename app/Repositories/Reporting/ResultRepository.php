<?php

namespace App\Repositories\Reporting;

use App\Repositories\Reporting\Contracts\ResultRepositoryInterface;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;

class ResultRepository implements ResultRepositoryInterface
{
    public function __construct(private readonly ConnectionInterface $connection) {}

    public function getRawResults(int|string $courseId, array $filters = []): Collection
    {
        $query = $this->connection->table('learn_items')
            ->join('learns', 'learn_items.learn_id', '=', 'learns.id')
            ->join('users', 'learns.participant_id', '=', 'users.id')
            ->leftJoin('user_meta as nip_meta', function ($join) {
                $join->on('nip_meta.user_id', '=', 'users.id')->where('nip_meta.key', '=', 'nip');
            })
            ->leftJoin('user_meta as jabatan_meta', function ($join) {
                $join->on('jabatan_meta.user_id', '=', 'users.id')->where('jabatan_meta.key', '=', 'nama_jabatan');
            })
            ->join('activities', function ($join) {
                $join->on('activities.course_id', '=', 'learns.course_id')
                    ->on('activities.id', '=', 'learn_items.activity_id');
            })
            ->join('courses', 'courses.id', '=', 'learns.course_id')
            ->where('learns.course_id', $courseId)
            ->whereNotNull('learns.finished_at');

        if (! empty($filters['nama'])) {
            $query->where('users.id', $filters['nama']);
        }

        if (! empty($filters['kegiatan'])) {
            $query->where('activities.id', $filters['kegiatan']);
        }

        return $query->orderBy('activities.id')
            ->orderBy('users.name')
            ->selectRaw("COALESCE(nip_meta.value, '-') as nip")
            ->addSelect([
                'users.name',
                'courses.title as materi',
                'activities.title as kegiatan',
                'activities.type as activity_type',
                'learn_items.response as response',
                'learn_items.updated_at as updated_at',
            ])
            ->selectRaw("COALESCE(jabatan_meta.value, '-') as jabatan")
            ->get();
    }
}
