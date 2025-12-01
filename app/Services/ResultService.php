<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ResultService
{
    public function getRawResults($courseId, $filters = [])
    {
        $query = DB::table('learn_items')
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

        // Filters
        if (!empty($filters['nama'])) {
            $query->where('users.id', $filters['nama']);
        }

        if (!empty($filters['kegiatan'])) {
            $query->where('activities.id', $filters['kegiatan']);
        }

        return $query->orderBy('activities.id')
            ->orderBy('users.name')
            ->select([
                DB::raw("COALESCE(nip_meta.value, '-') as nip"),
                'users.name',
                DB::raw("COALESCE(jabatan_meta.value, '-') as jabatan"),
                'courses.title as materi',
                'activities.title as kegiatan',
                'activities.type as activity_type',
                'learn_items.response as response',
            ])
            ->get();
    }

    public function groupAverageScores($data)
    {
        return collect($data)->groupBy('name')->map(function ($rows, $name) {
            $scores = [];

            foreach ($rows as $row) {
                if (in_array($row->activity_type, ['post-test', 'pre-test'])) {
                    try {
                        $response = @unserialize($row->response);
                        if (is_array($response)) {
                            foreach ($response as $item) {
                                if (isset($item['score'])) {
                                    $scores[] = $item['score'];
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        // optional: log error
                    }
                }
            }

            return [
                'nip' => $rows->first()->nip,
                'name' => $name,
                'jabatan' => $rows->first()->jabatan,
                'avg_score' => count($scores) > 0 ? round(array_sum($scores) / count($scores), 2) : 0,
            ];
        })->values();
    }
}
