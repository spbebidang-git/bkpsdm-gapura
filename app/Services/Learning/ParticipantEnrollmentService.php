<?php

namespace App\Services\Learning;

use App\Models\Course\Course;
use App\Models\Course\Schedule;
use App\Models\Learning\Enrollment;
use App\Models\Learning\Learn;
use Carbon\CarbonInterface;

class ParticipantEnrollmentService
{
    public function activate(Course $course, Schedule $schedule, string $participantId): Enrollment
    {
        $scheduleId = $course->access_mode === 'scheduled' ? $schedule->id : null;
        $enrollment = Enrollment::query()->where('participant_id', $participantId)
            ->where('course_id', $course->id)->orderBy('id')->lockForUpdate()->first();
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
            $previous = Enrollment::withTrashed()->where('participant_id', $participantId)
                ->where('course_id', $course->id)->where('schedule_id', $scheduleId)
                ->whereNotNull('deleted_at')->latest('deleted_at')->first();
            if ($previous && $previous->access_starts_at && $previous->access_ends_at) {
                $duration = $previous->access_starts_at->diffInSeconds($previous->access_ends_at);
                $startsAt = $now->copy();
                $endsAt = $startsAt->copy()->addSeconds($duration);
            }
        } elseif ($course->default_access_hours) {
            $endsAt = $now->copy()->addHours((int) $course->default_access_hours);
        }

        return Enrollment::query()->create([
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

        return Enrollment::query()->where('participant_id', $participantId)
            ->where('course_id', $course->id)->where('schedule_id', $scheduleId)->first();
    }

    public function canRead(Enrollment $enrollment, ?CarbonInterface $at = null): bool
    {
        $at ??= now();
        $learn = Learn::query()->where('enrollment_id', $enrollment->id)->first();

        if ($learn?->finished_at) return true;
        if ($enrollment->status !== 'active') return false;
        if ($at->lt($enrollment->access_starts_at)) return false;

        return ! $enrollment->access_ends_at || $at->lte($enrollment->access_ends_at);
    }

    public function canWrite(Enrollment $enrollment, ?CarbonInterface $at = null): bool
    {
        $at ??= now();
        $learn = Learn::query()->where('enrollment_id', $enrollment->id)->first();
        return ! $learn?->finished_at && $enrollment->status === 'active'
            && $at->gte($enrollment->access_starts_at)
            && (! $enrollment->access_ends_at || $at->lte($enrollment->access_ends_at));
    }
}
