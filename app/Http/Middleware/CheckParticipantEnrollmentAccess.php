<?php

namespace App\Http\Middleware;

use App\Models\Course\Course;
use App\Models\Course\Schedule;
use App\Models\Course\Activity\Activity;
use App\Services\Learning\ParticipantEnrollmentService;
use App\Models\Learning\LearnItem;
use Closure;
use Illuminate\Http\Request;

class CheckParticipantEnrollmentAccess
{
    public function handle(Request $request, Closure $next, string $mode = 'read')
    {
        if (! config('participant.enrollment_access_enforced', true)) return $next($request);

        $course = $request->route('course');
        $schedule = $request->route('schedule') ?? $request->route('scheduleId');
        if (! $schedule instanceof Schedule && $schedule) $schedule = Schedule::query()->find($schedule);
        if (! $course instanceof Course || ! $schedule instanceof Schedule || $schedule->course_id !== $course->id) {
            abort(404);
        }
        $activity = $request->route('activity');
        if ($activity && (! $activity instanceof Activity || $activity->course_id !== $course->id)) abort(404);

        $service = app(ParticipantEnrollmentService::class);
        $enrollment = $service->forRequest((string) $request->user()->id, $course, $schedule);
        if (! $enrollment) {
            return redirect()->route('participant.course.index')->with('danger', 'Anda belum terdaftar pada kelas ini.');
        }

        $allowed = $mode === 'write' ? $service->canWrite($enrollment) : $service->canRead($enrollment);
        if (! $allowed) {
            return redirect()->route('participant.course.show', $course->slug)
                ->with('danger', 'Masa akses kelas ini telah berakhir atau belum dimulai.');
        }

        if ($activity && LearnItem::query()
            ->whereHas('learn', fn ($query) => $query->where('enrollment_id', $enrollment->id))
            ->where('activity_id', $activity->id)->whereNotNull('locked_at')->exists()) {
            $lockedIds = LearnItem::query()
                ->whereHas('learn', fn ($query) => $query->where('enrollment_id', $enrollment->id))
                ->whereNotNull('locked_at')->pluck('activity_id');
            $availableActivity = Activity::query()->where('course_id', $course->id)
                ->whereNotIn('id', $lockedIds)->orderBy('sort_order')->orderBy('id')->first();
            $destination = $availableActivity
                ? route('participant.learn.instruction.index', [$course->slug, $schedule->id, $availableActivity->id])
                : route('participant.rating.index', [$course->slug, $schedule->id]);
            return redirect()->to($destination)
                ->with('danger', 'Kegiatan ini sudah selesai dan dikunci setelah Anda melanjutkan.');
        }

        $activeMeeting = LearnItem::query()
            ->where('status', 'in_progress')
            ->whereHas('learn', fn ($query) => $query->where('enrollment_id', $enrollment->id))
            ->whereIn('activity_id', Activity::query()->select('id')->where('course_id', $course->id)->where('type', 'meeting'))
            ->first();

        if ($activeMeeting) {
            $isActiveMaterial = $activity && (string) $activity->id === (string) $activeMeeting->activity_id
                && $request->routeIs('participant.learn.index', 'participant.learn.store');
            if (! $isActiveMaterial) {
                return redirect()->route('participant.learn.index', [$course->slug, $schedule->id, $activeMeeting->activity_id])
                    ->with('danger', 'Selesaikan pertemuan yang sedang berlangsung sebelum berpindah kegiatan.');
            }
        }

        $request->attributes->set('participant_enrollment', $enrollment);
        return $next($request);
    }
}
