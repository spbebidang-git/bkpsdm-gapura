<?php

namespace App\Repositories\Learning\Contracts;

use App\Models\Course\Course;
use App\Models\Course\Schedule;
use App\Models\Identity\User;
use App\Models\Learning\Learn;
use Illuminate\Support\Collection;

interface LearnBulkImportRepositoryInterface
{
    public function activities(string $courseId): Collection;
    public function firstSchedule(string $courseId): ?Schedule;
    public function existingLearns(string $courseId, array $userIds): Collection;
    public function users(array $userIds): Collection;
    public function nipHashes(array $hashes): Collection;
    public function course(string $courseId): Course;
    public function createLearn(array $data): Learn;
    public function putLearnItem(string $learnId, string $activityId, array $data): void;
    public function attachParticipant(Schedule $schedule, string $userId): void;
    public function createUser(array $data): User;
    public function emailExists(string $email): bool;
}
