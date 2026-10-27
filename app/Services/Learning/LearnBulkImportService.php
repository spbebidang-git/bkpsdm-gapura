<?php

namespace App\Services\Learning;

use App\Services\Learning\LearnLifecycleService;
use App\Services\Learning\ParticipantEnrollmentService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Repositories\Learning\Contracts\LearnBulkImportRepositoryInterface;

class LearnBulkImportService
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

    public function __construct(private readonly LearnBulkImportRepositoryInterface $importData) {}

    public function run(Collection $rows, string $courseId, string $finishedAt, bool $createMissingUsers = false): array
    {
        $this->courseId = $courseId;
        $this->finishedAt = $finishedAt;
        $this->createMissingUsers = $createMissingUsers;
        $this->collection($rows);
        return $this->results;
    }

    private function collection(Collection $rows)
    {
        $activities = $this->importData->activities($this->courseId);

        if ($activities->isEmpty()) {
            $this->results['errors'][] = 'Kelas tidak memiliki kegiatan.';
            return;
        }

        $finishedAt = Carbon::parse($this->finishedAt);
        $firstSchedule = $this->importData->firstSchedule($this->courseId);

        // Batch lookup NIP → user_id via nip_hash
        $nips = $rows->map(fn ($row) => trim((string) ($row['nip'] ?? '')))
            ->filter()
            ->unique()
            ->values();

        $nipToUserId = $this->resolveNipsToUserIds($nips);

        $userIds = $nipToUserId->values()->unique()->filter()->all();

        $existingLearns = empty($userIds)
            ? collect()
            : $this->importData->existingLearns($this->courseId, $userIds);

        $users = empty($userIds)
            ? collect()
            : $this->importData->users($userIds);

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
                    ? app(ParticipantEnrollmentService::class)->activate($this->importData->course($this->courseId), $firstSchedule, (string) $user->id)
                    : null;

                $learn = $existingLearn ?? $this->importData->createLearn([
                    'participant_id' => $user->id,
                    'course_id' => $this->courseId,
                    'enrollment_id' => $enrollment?->id,
                ]);

                app(LearnLifecycleService::class)->finish($learn, $finishedAt);

                $existingLearns->put($user->id, $learn);

                if ($firstSchedule) {
                    $this->importData->attachParticipant($firstSchedule, (string) $user->id);
                }

                foreach ($activities as $activity) {
                    $this->importData->putLearnItem((string) $learn->id, (string) $activity->id, [
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

        $rows = $this->importData->nipHashes($hashToNip->keys()->all());

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

        $user = $this->importData->createUser([
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
        if (! $this->importData->emailExists($email)) {
            return $email;
        }

        $email = $slug . '.' . $nip . '@gresikkab.go.id';
        if (! $this->importData->emailExists($email)) {
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
