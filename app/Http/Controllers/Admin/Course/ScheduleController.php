<?php

namespace App\Http\Controllers\Admin\Course;

use App\Models\Course\Course;
use App\Models\Course\Schedule;
use App\Models\Identity\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ScheduleController
{
    public function index(Course $course)
    {
        abort_unless($course->access_mode === 'scheduled', 404);
        $this->assertCourseAccess($course);

        $schedules = $course->schedules()->orderBy('date')->get();
        $hasMultiple = $schedules->count() > 1;
        return Inertia::render('Admin/Course/Schedules', [
            'course' => ['id' => $course->id, 'slug' => $course->slug, 'title' => $course->title, 'status' => $course->status],
            'schedules' => $schedules->map(fn (Schedule $schedule) => $this->present($schedule, $course->status === 'publish' && ! $hasMultiple)),
        ]);
    }

    public function store(Request $request, Course $course)
    {
        abort_unless($course->access_mode === 'scheduled', 404);
        $this->assertCourseAccess($course);
        $data = $this->validateSchedule($request);
        $schedule = DB::transaction(fn () => $course->schedules()->create($data));

        return to_route('admin.course.schedules.index', $course->slug)->with('success', 'Jadwal berhasil ditambahkan. Kelas tetap draft; edit kelas untuk memublikasikannya.');
    }

    public function update(Request $request, Course $course, Schedule $schedule)
    {
        abort_unless($course->access_mode === 'scheduled', 404);
        $this->assertCourseAccess($course);
        abort_unless((string) $schedule->course_id === (string) $course->id, 404);
        $data = $this->validateSchedule($request);

        DB::transaction(function () use ($course, $schedule, $data) {
            $locked = Schedule::query()->whereKey($schedule->id)->lockForUpdate()->firstOrFail();
            $registered = $this->participantIds($locked)->count();
            if ($registered > 0 && ($data['date'] !== $locked->getRawOriginal('date') || $data['time_from'] !== $this->time($locked->getRawOriginal('time_from')) || $data['time_to'] !== $this->time($locked->getRawOriginal('time_to')))) {
                throw ValidationException::withMessages(['schedule' => 'Tanggal dan waktu tidak dapat diubah karena peserta sudah terdaftar.']);
            }
            if ($data['quota'] < $registered) {
                throw ValidationException::withMessages(['quota' => "Kuota minimal sebanyak peserta terdaftar ({$registered})."]);
            }
            $locked->update($data);
        });

        return to_route('admin.course.schedules.index', $course->slug)->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function destroy(Course $course, Schedule $schedule)
    {
        abort_unless($course->access_mode === 'scheduled', 404);
        $this->assertCourseAccess($course);
        abort_unless((string) $schedule->course_id === (string) $course->id, 404);

        DB::transaction(function () use ($course, $schedule) {
            $lockedCourse = Course::query()->whereKey($course->id)->lockForUpdate()->firstOrFail();
            $locked = Schedule::query()->whereKey($schedule->id)->lockForUpdate()->firstOrFail();
            if ($this->participantIds($locked)->isNotEmpty()) {
                throw ValidationException::withMessages(['schedule' => 'Jadwal dengan peserta terdaftar tidak dapat dihapus.']);
            }
            if ($lockedCourse->status === 'publish' && $lockedCourse->schedules()->count() <= 1) {
                throw ValidationException::withMessages(['schedule' => 'Kelas publikasi harus memiliki minimal satu jadwal. Ubah kelas menjadi draft terlebih dahulu.']);
            }
            $locked->delete();
        });

        return to_route('admin.course.schedules.index', $course->slug)->with('success', 'Jadwal berhasil dihapus.');
    }

    private function validateSchedule(Request $request): array
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'time_from' => ['required', 'date_format:H:i'],
            'time_to' => ['required', 'date_format:H:i', 'after:time_from'],
            'quota' => ['required', 'integer', 'min:1'],
        ], [
            'required' => ':attribute wajib diisi.', 'date' => ':attribute harus berupa tanggal yang valid.',
            'date_format' => ':attribute harus menggunakan format jam yang valid.', 'after' => 'Waktu selesai harus setelah waktu mulai.',
            'integer' => ':attribute harus berupa bilangan bulat.', 'min' => ':attribute minimal :min.',
        ], ['date' => 'Tanggal', 'time_from' => 'Waktu mulai', 'time_to' => 'Waktu selesai', 'quota' => 'Kuota']);
        return $data;
    }

    private function present(Schedule $schedule, bool $lastPublished): array
    {
        $registered = $this->participantIds($schedule)->count();
        return ['id' => $schedule->id, 'date' => $schedule->getRawOriginal('date'), 'time_from' => $this->time($schedule->getRawOriginal('time_from')), 'time_to' => $this->time($schedule->getRawOriginal('time_to')), 'quota' => $schedule->getRawOriginal('quota'), 'participant_count' => $registered, 'can_edit_details' => $registered === 0, 'can_delete' => $registered === 0 && ! $lastPublished];
    }

    private function participantIds(Schedule $schedule)
    {
        $legacy = DB::table('schedule_user')->where('schedule_id', $schedule->id)->select('participant_id');
        $enrollments = DB::table('enrollments')->where('schedule_id', $schedule->id)->select('participant_id');
        return $legacy->union($enrollments)->distinct()->pluck('participant_id');
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
