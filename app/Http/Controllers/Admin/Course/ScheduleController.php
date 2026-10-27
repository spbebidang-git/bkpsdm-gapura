<?php

namespace App\Http\Controllers\Admin\Course;

use App\Models\Course\Course;
use App\Models\Course\Schedule;
use App\Models\Identity\User;
use App\Http\Requests\Course\ScheduleRequest;
use App\Services\Course\ScheduleService;
use Inertia\Inertia;

class ScheduleController
{
    public function __construct(private readonly ScheduleService $schedules) {}

    public function index(Course $course)
    {
        abort_unless($course->access_mode === 'scheduled', 404);
        $this->assertCourseAccess($course);

        $schedules = $this->schedules->forCourse($course);
        $hasMultiple = $schedules->count() > 1;
        return Inertia::render('Admin/Course/Schedules', [
            'course' => ['id' => $course->id, 'slug' => $course->slug, 'title' => $course->title, 'status' => $course->status],
            'schedules' => $schedules->map(fn (Schedule $schedule) => $this->present($schedule, $course->status === 'publish' && ! $hasMultiple)),
        ]);
    }

    public function store(ScheduleRequest $request, Course $course)
    {
        abort_unless($course->access_mode === 'scheduled', 404);
        $this->assertCourseAccess($course);
        $this->schedules->create($course, $request->validated());

        return to_route('admin.course.schedules.index', $course->slug)->with('success', 'Jadwal berhasil ditambahkan. Kelas tetap draft; edit kelas untuk memublikasikannya.');
    }

    public function update(ScheduleRequest $request, Course $course, Schedule $schedule)
    {
        abort_unless($course->access_mode === 'scheduled', 404);
        $this->assertCourseAccess($course);
        abort_unless((string) $schedule->course_id === (string) $course->id, 404);
        $this->schedules->update($schedule, $request->validated());

        return to_route('admin.course.schedules.index', $course->slug)->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function destroy(Course $course, Schedule $schedule)
    {
        abort_unless($course->access_mode === 'scheduled', 404);
        $this->assertCourseAccess($course);
        abort_unless((string) $schedule->course_id === (string) $course->id, 404);

        $this->schedules->delete($course, $schedule);

        return to_route('admin.course.schedules.index', $course->slug)->with('success', 'Jadwal berhasil dihapus.');
    }

    private function present(Schedule $schedule, bool $lastPublished): array
    {
        $registered = $this->schedules->participantCount($schedule);
        return ['id' => $schedule->id, 'date' => $schedule->getRawOriginal('date'), 'time_from' => $this->time($schedule->getRawOriginal('time_from')), 'time_to' => $this->time($schedule->getRawOriginal('time_to')), 'quota' => $schedule->getRawOriginal('quota'), 'participant_count' => $registered, 'can_edit_details' => $registered === 0, 'can_delete' => $registered === 0 && ! $lastPublished];
    }

    private function time(?string $value): ?string
    {
        return $value ? \Carbon\Carbon::parse($value)->format('H:i') : null;
    }

    private function assertCourseAccess(Course $course): void
    {
        $user = request()->user();
        if ($user instanceof User && $user->hasRole('trainer') && ! $user->hasAnyRole(['super_admin', 'administrator'])) {
            abort_unless(app(\App\Services\Course\CourseAudience::class)->canManage($course, $user), 403);
            abort_unless($course->trainers()->where('user_id', $user->id)->exists(), 403);
        }
    }
}
