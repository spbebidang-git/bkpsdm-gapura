<?php

namespace App\Services\Learning;

use App\Models\Course\Course;
use App\Models\Course\Schedule;
use App\Models\Learning\Enrollment;
use App\Models\Learning\Learn;
use App\Repositories\Learning\Contracts\EnrollmentRepositoryInterface;
use App\Repositories\Learning\Contracts\LearnRepositoryInterface;
use App\Services\Course\CourseService;
use App\Repositories\Course\Contracts\ScheduleRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class ParticipantEnrollmentService
{
    public function __construct(
        private readonly EnrollmentRepositoryInterface $enrollments,
        private readonly LearnRepositoryInterface $learns,
        private readonly CourseService $courses,
        private readonly ParticipantNotificationService $notifications,
        private readonly ScheduleRepositoryInterface $schedules,
    ) {}

    public function archiveExpired(string $participantId, ?string $courseId = null): void
    {
        foreach ($this->enrollments->expiredIds($participantId, $courseId) as $id) {
                DB::transaction(function () use ($id): void {
                    $enrollment = $this->enrollments->lock($id);
                    if (! $enrollment || ! $enrollment->access_ends_at || ! $enrollment->access_ends_at->isPast()) return;
                    $learn = $this->learns->lockByEnrollment($enrollment->id);
                    if ($learn?->finished_at) return;
                    $this->notifications->deadline($enrollment, 'expired');
                    if ($learn) $this->learns->deleteWithItems($learn);
                    $this->enrollments->delete($enrollment);
                });
        }
    }

    public function activate(Course $course, Schedule $schedule, string $participantId): Enrollment
    {
        $scheduleId = $course->access_mode === 'scheduled' ? $schedule->id : null;
        $enrollment = $this->enrollments->lockByParticipantAndCourse($participantId, $course->id);
        if ($enrollment) return $enrollment;

        $now = now();
        $startsAt = $now;
        $endsAt = null;

        if ($course->access_mode === 'scheduled') {
            $date = $schedule->getRawOriginal('date');
            $startsAt = $schedule->access_from
                ?? ($date ? now()->parse($date.' '.($schedule->getRawOriginal('time_from') ?: '00:00:00')) : $now);
            $endsAt = $schedule->access_to
                ?? ($date ? now()->parse($date.' '.($schedule->getRawOriginal('time_to') ?: '23:59:59')) : null);
            $previous = $this->enrollments->previousArchivedForSchedule($participantId, $course->id, $scheduleId);
            if ($previous && $previous->access_starts_at && $previous->access_ends_at) {
                $duration = $previous->access_starts_at->diffInSeconds($previous->access_ends_at);
                $startsAt = $now->copy();
                $endsAt = $startsAt->copy()->addSeconds($duration);
            }
        } elseif ($course->default_access_hours) {
            $endsAt = $now->copy()->addHours((int) $course->default_access_hours);
        }

        return $this->enrollments->create([
            'participant_id' => $participantId,
            'course_id' => $course->id,
            'schedule_id' => $scheduleId,
            'status' => 'active',
            'enrolled_at' => $now,
            'access_starts_at' => $startsAt,
            'access_ends_at' => $endsAt,
        ]);
    }

    public function forRequest(string $participantId, Course $course, Schedule $schedule): ?Enrollment
    {
        $scheduleId = $course->access_mode === 'scheduled' ? $schedule->id : null;

        return $this->enrollments->findByParticipantCourseAndSchedule($participantId, $course->id, $scheduleId);
    }

    public function canRead(Enrollment $enrollment, ?CarbonInterface $at = null): bool
    {
        $at ??= now();
        $learn = $this->learns->findByEnrollment($enrollment->id);

        if ($learn?->finished_at) return true;
        if ($enrollment->status !== 'active') return false;
        if ($at->lt($enrollment->access_starts_at)) return false;

        return ! $enrollment->access_ends_at || $at->lte($enrollment->access_ends_at);
    }

    public function canWrite(Enrollment $enrollment, ?CarbonInterface $at = null): bool
    {
        $at ??= now();
        $learn = $this->learns->findByEnrollment($enrollment->id);
        return ! $learn?->finished_at && $enrollment->status === 'active'
            && $at->gte($enrollment->access_starts_at)
            && (! $enrollment->access_ends_at || $at->lte($enrollment->access_ends_at));
    }

    public function hasArchived(string $participantId, string $courseId): bool
    {
        return $this->enrollments->hasArchived($participantId, $courseId);
    }

    public function reactivate(Course $course, string $participantId): void
    {
        $this->archiveExpired($participantId, (string) $course->id);
        $previous = $this->enrollments->latestArchived($participantId, $course->id);
        abort_unless($previous, 404);
        $schedule = $course->access_mode === 'scheduled'
            ? $this->schedules->findByCourse($course->id, $previous->schedule_id)
            : $this->schedules->firstForCourse($course->id);
        abort_unless($schedule, 404);
        abort_unless($this->schedules->participantHasSchedule($schedule->id, $participantId), 403);

        DB::transaction(function () use ($course, $schedule, $participantId): void {
            $enrollment = $this->activate($course, $schedule, $participantId);
            abort_unless($enrollment->status === 'active', 409);
            if ($enrollment->wasRecentlyCreated) $this->courses->generatePrePostTestVariant($participantId, $course);
        });
    }

    public function reset(Course $course, string $participantId): void
    {
        DB::transaction(function () use ($course, $participantId): void {
            $enrollment = $this->enrollments->lockByParticipantAndCourse($participantId, $course->id);
            abort_unless($enrollment, 404);
            abort_unless(in_array($enrollment->status, ['active', 'completed', 'expired'], true), 403);
            $learn = $this->learns->lockByEnrollment($enrollment->id);
            if ($learn) $this->learns->deleteWithItems($learn);
            $this->enrollments->delete($enrollment);
        });
    }
}
