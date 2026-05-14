<?php

namespace App\Imports;

use App\Models\Course\Activity;
use App\Models\Learn\Learn;
use App\Models\Learn\LearnItem;
use App\Models\User;
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

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 karena heading row + 0-indexed

            try {
                // Cari user berdasarkan NIP
                $nip = trim($row['nip'] ?? '');

                if (empty($nip)) {
                    $this->results['failed']++;
                    $this->results['errors'][] = "Baris {$rowNumber}: NIP kosong.";
                    continue;
                }

                $userId = DB::table('user_meta')
                    ->where('key', 'nip')
                    ->where('value', $nip)
                    ->value('user_id');

                $user = $userId ? User::find($userId) : null;

                if (!$user) {
                    $this->results['failed']++;
                    $this->results['errors'][] = "Baris {$rowNumber}: NIP '{$nip}' tidak ditemukan.";
                    continue;
                }

                // Cek apakah sudah ada learn yang sudah finished
                $existingLearn = Learn::where('participant_id', $user->id)
                    ->where('course_id', $this->courseId)
                    ->first();

                if ($existingLearn && $existingLearn->finished_at) {
                    $this->results['skipped']++;
                    $this->results['errors'][] = "Baris {$rowNumber}: {$user->email} sudah menyelesaikan kursus ini.";
                    continue;
                }

                DB::beginTransaction();

                // Buat atau gunakan learn yang sudah ada
                $learn = $existingLearn ?? Learn::create([
                    'participant_id' => $user->id,
                    'course_id' => $this->courseId,
                ]);

                // Set finished_at
                $learn->finished_at = Carbon::parse($this->finishedAt);
                $learn->save();

                // Aktivasi kelas: masukkan ke schedule_user (ambil jadwal pertama dari course ini)
                $firstSchedule = \App\Models\Course\Schedule::where('course_id', $this->courseId)->first();
                if ($firstSchedule) {
                    // Gunakan attach/syncWithoutDetaching agar tidak error kalau sudah ada
                    $firstSchedule->participants()->syncWithoutDetaching([$user->id]);
                }

                // Buat learn_items untuk setiap activity
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
