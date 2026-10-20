<?php

namespace App\Imports;

use App\Models\Course\Activity\Activity;
use App\Models\Course\Schedule;
use App\Models\Learning\Learn;
use App\Services\Learning\LearnLifecycleService;
use App\Services\Learning\ParticipantEnrollmentService;
use App\Models\Course\Course;
use App\Models\Learning\LearnItem;
use App\Models\Identity\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class LearnBulkImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    protected $courseId;
    protected $finishedAt;
    protected bool $createMissingUsers;
    protected $results = [
        'success' => 0,
        'skipped' => 0,
        'failed' => 0,
        'created_users' => 0,
        'errors' => [],
    ];

    public function __construct($courseId, $finishedAt, bool $createMissingUsers = false)
    {
        $this->courseId = $courseId;
        $this->finishedAt = $finishedAt;
        $this->createMissingUsers = $createMissingUsers;
    }

    public function collection(Collection $rows)
    {
        $activities = Activity::where('course_id', $this->courseId)->get();

        if ($activities->isEmpty()) {
            $this->results['errors'][] = 'Kelas tidak memiliki kegiatan.';
            return;
        }

        $finishedAt = Carbon::parse($this->finishedAt);
        $firstSchedule = Schedule::where('course_id', $this->courseId)->first();

        // Batch lookup NIP → user_id via nip_hash
        $nips = $rows->map(fn ($row) => trim((string) ($row['nip'] ?? '')))
            ->filter()
            ->unique()
            ->values();

        $nipToUserId = $this->resolveNipsToUserIds($nips);

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

        $seenNips = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            try {
                $nip = trim((string) ($row['nip'] ?? ''));
                $nama = trim((string) ($row['nama'] ?? ''));

                if ($nip === '') {
                    $this->results['failed']++;
                    $this->results['errors'][] = "Baris {$rowNumber}: NIP kosong.";
                    continue;
                }

                if (isset($seenNips[$nip])) {
                    $this->results['skipped']++;
                    $this->results['errors'][] = "Baris {$rowNumber}: NIP '{$nip}' duplikat (sudah diproses baris {$seenNips[$nip]}).";
                    continue;
                }
                $seenNips[$nip] = $rowNumber;

                $userId = $nipToUserId[$nip] ?? null;
                $user = $userId ? $users->get($userId) : null;

                if (!$user) {
                    if (!$this->createMissingUsers) {
                        $this->results['failed']++;
                        $this->results['errors'][] = "Baris {$rowNumber}: NIP '{$nip}' tidak ditemukan.";
                        continue;
                    }

                    $user = $this->createStubUser($nip, $nama);
                    $nipToUserId[$nip] = $user->id;
                    $users->put($user->id, $user);
                    $this->results['created_users']++;
                }

                $existingLearn = $existingLearns->get($user->id);

                if ($existingLearn && $existingLearn->finished_at) {
                    $this->results['skipped']++;
                    $this->results['errors'][] = "Baris {$rowNumber}: {$user->email} sudah menyelesaikan kelas ini.";
                    continue;
                }

                DB::beginTransaction();

                $enrollment = $firstSchedule
                    ? app(ParticipantEnrollmentService::class)->activate(Course::findOrFail($this->courseId), $firstSchedule, (string) $user->id)
                    : null;

                $learn = $existingLearn ?? Learn::create([
                    'participant_id' => $user->id,
                    'course_id' => $this->courseId,
                    'enrollment_id' => $enrollment?->id,
                ]);

                app(LearnLifecycleService::class)->finish($learn, $finishedAt);

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
                Log::error('Baris impor pembelajaran gagal.', ['event' => 'learn.import.row_failed', 'row_number' => $rowNumber, 'exception' => $th]);
            }
        }
    }

    /**
     * @return \Illuminate\Support\Collection<string, int> nip => user_id
     */
    protected function resolveNipsToUserIds(Collection $nips): Collection
    {
        if ($nips->isEmpty()) {
            return collect();
        }

        $hashToNip = $nips->mapWithKeys(fn ($nip) => [
            hash_hmac('sha256', $nip, config('app.key')) => $nip,
        ]);

        $rows = DB::table('user_meta')
            ->where('key', 'nip_hash')
            ->whereIn('value', $hashToNip->keys()->all())
            ->get(['user_id', 'value']);

        $nipToUserId = collect();
        foreach ($rows as $row) {
            $nip = $hashToNip[$row->value] ?? null;
            if ($nip !== null) {
                $nipToUserId[$nip] = $row->user_id;
            }
        }

        return $nipToUserId;
    }

    /**
     * @return object{id: int, email: string}
     */
    protected function createStubUser(string $nip, string $nama): object
    {
        $name = $nama !== '' ? $nama : "NIP {$nip}";
        $email = $this->uniqueEmailFromName($name, $nip);

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'nip' => $nip,
        ]);

        $user->assignRole('participant');
        update_user_meta($user->id, 'nip', $nip);
        update_user_meta($user->id, 'status_pegawai', '-');

        return (object) ['id' => $user->id, 'email' => $user->email];
    }

    protected function uniqueEmailFromName(string $name, string $nip): string
    {
        $slug = Str::slug($name, '.');
        if ($slug === '') {
            $slug = 'user.' . $nip;
        }

        $email = $slug . '@gresikkab.go.id';
        if (! User::where('email', $email)->exists()) {
            return $email;
        }

        $email = $slug . '.' . $nip . '@gresikkab.go.id';
        if (! User::where('email', $email)->exists()) {
            return $email;
        }

        // edge: nip suffix also taken
        throw new \RuntimeException("Email bentrok untuk NIP '{$nip}' ({$email}).");
    }

    public function getResults(): array
    {
        return $this->results;
    }
}
