<?php

namespace App\Imports;

use App\Models\Course\Activity;
use App\Models\Course\Schedule;
use App\Models\Learn\Learn;
use App\Models\Learn\LearnItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class LearnBulkImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    protected $courseId;
    protected $finishedAt;
    protected $results = [
        'success' => 0,
        'skipped' => 0,
        'failed' => 0,
        'errors' => [],
    ];

    public function __construct($courseId, $finishedAt)
    {
        $this->courseId = $courseId;
        $this->finishedAt = $finishedAt;
    }

    public function collection(Collection $rows)
    {
        $activities = Activity::where('course_id', $this->courseId)->get();

        if ($activities->isEmpty()) {
            $this->results['errors'][] = 'Course tidak memiliki activity.';
            return;
        }

        $finishedAt = Carbon::parse($this->finishedAt);
        $firstSchedule = Schedule::where('course_id', $this->courseId)->first();

        // Batch lookup NIP → user_id
        $nips = $rows->map(fn ($row) => trim((string) ($row['nip'] ?? '')))
            ->filter()
            ->unique()
            ->values();

        $nipToUserId = $nips->isEmpty()
            ? collect()
            : DB::table('user_meta')
                ->where('key', 'nip')
                ->whereIn('value', $nips->all())
                ->pluck('user_id', 'value');

        $userIds = $nipToUserId->values()->unique()->filter()->all();

        $existingLearns = empty($userIds)
            ? collect()
            : Learn::where('course_id', $this->courseId)
                ->whereIn('participant_id', $userIds)
                ->get()
                ->keyBy('participant_id');

        $users = empty($userIds)
            ? collect()
            : DB::table('users')->whereIn('id', $userIds)->get(['id', 'email'])->keyBy('id');

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            try {
                $nip = trim((string) ($row['nip'] ?? ''));

                if ($nip === '') {
                    $this->results['failed']++;
                    $this->results['errors'][] = "Baris {$rowNumber}: NIP kosong.";
                    continue;
                }

                $userId = $nipToUserId[$nip] ?? null;
                $user = $userId ? $users->get($userId) : null;

                if (!$user) {
                    $this->results['failed']++;
                    $this->results['errors'][] = "Baris {$rowNumber}: NIP '{$nip}' tidak ditemukan.";
                    continue;
                }

                $existingLearn = $existingLearns->get($user->id);

                if ($existingLearn && $existingLearn->finished_at) {
                    $this->results['skipped']++;
                    $this->results['errors'][] = "Baris {$rowNumber}: {$user->email} sudah menyelesaikan kursus ini.";
                    continue;
                }

                DB::beginTransaction();

                $learn = $existingLearn ?? Learn::create([
                    'participant_id' => $user->id,
                    'course_id' => $this->courseId,
                ]);

                $learn->finished_at = $finishedAt;
                $learn->save();

                $existingLearns->put($user->id, $learn);

                if ($firstSchedule) {
                    $firstSchedule->participants()->syncWithoutDetaching([$user->id]);
                }

                foreach ($activities as $activity) {
                    LearnItem::updateOrCreate(
                        [
                            'learn_id' => $learn->id,
                            'activity_id' => $activity->id,
                        ],
                        [
                            'title' => $activity->title,
                            'type' => $activity->type,
                            'response' => null,
                            'status' => 'done',
                        ]
                    );
                }

                DB::commit();
                $this->results['success']++;
            } catch (\Throwable $th) {
                DB::rollBack();
                $this->results['failed']++;
                $this->results['errors'][] = "Baris {$rowNumber}: " . $th->getMessage();
                Log::error("LearnBulkImport error row {$rowNumber}: " . $th->getMessage());
            }
        }
    }

    public function getResults(): array
    {
        return $this->results;
    }
}
