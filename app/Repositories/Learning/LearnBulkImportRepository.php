<?php

namespace App\Repositories\Learning;

use App\Models\Course\Activity\Activity;
use App\Models\Course\Course;
use App\Models\Course\Schedule;
use App\Models\Identity\User;
use App\Models\Learning\Learn;
use App\Models\Learning\LearnItem;
use App\Repositories\Learning\Contracts\LearnBulkImportRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LearnBulkImportRepository implements LearnBulkImportRepositoryInterface
{
    public function activities(string $courseId): Collection
    {
        return Activity::where('course_id', $courseId)->get();
    }

    public function firstSchedule(string $courseId): ?Schedule
    {
        return Schedule::where('course_id', $courseId)->first();
    }

    public function existingLearns(string $courseId, array $userIds): Collection
    {
        return Learn::where('course_id', $courseId)->whereIn('participant_id', $userIds)
            ->get()->keyBy('participant_id');
    }

    public function users(array $userIds): Collection
    {
        return DB::table('users')->whereIn('id', $userIds)->get(['id', 'email'])->keyBy('id');
    }

    public function nipHashes(array $hashes): Collection
    {
        return DB::table('user_meta')->where('key', 'nip_hash')->whereIn('value', $hashes)
            ->get(['user_id', 'value']);
    }

    public function course(string $courseId): Course
    {
        return Course::findOrFail($courseId);
    }

    public function createLearn(array $data): Learn
    {
        return Learn::create($data);
    }

    public function putLearnItem(string $learnId, string $activityId, array $data): void
    {
        LearnItem::updateOrCreate(['learn_id' => $learnId, 'activity_id' => $activityId], $data);
    }

    public function attachParticipant(Schedule $schedule, string $userId): void
    {
        $schedule->participants()->syncWithoutDetaching([$userId]);
    }

    public function createUser(array $data): User
    {
        return User::create($data);
    }

    public function emailExists(string $email): bool
    {
        return User::where('email', $email)->exists();
    }
}
