<?php

namespace App\Services\Course;

use App\Models\Course\Course;
use App\Models\Course\Schedule;
use App\Repositories\Course\Contracts\CourseRepositoryInterface;
use App\Repositories\Course\Contracts\ScheduleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScheduleService
{
    public function __construct(
        private readonly CourseRepositoryInterface $courses,
        private readonly ScheduleRepositoryInterface $schedules,
    ) {}

    public function forCourse(Course $course): Collection
    {
        return $this->schedules->getByCourse($course->id);
    }

    public function create(Course $course, array $data): Schedule
    {
        return DB::transaction(fn () => $this->schedules->create(['course_id' => $course->id, ...$data]));
    }

    public function update(Schedule $schedule, array $data): void
    {
        DB::transaction(function () use ($schedule, $data): void {
            $locked = $this->schedules->lock($schedule->id);
            $registered = $this->schedules->participantCount($locked->id);
            if ($registered > 0 && ($data['date'] !== $locked->getRawOriginal('date')
                || $data['time_from'] !== $this->time($locked->getRawOriginal('time_from'))
                || $data['time_to'] !== $this->time($locked->getRawOriginal('time_to')))) {
                throw ValidationException::withMessages(['schedule' => 'Tanggal dan waktu tidak dapat diubah karena pengguna sudah terdaftar.']);
            }
            if ($data['quota'] < $registered) {
                throw ValidationException::withMessages(['quota' => "Kuota minimal sebanyak pengguna terdaftar ({$registered})."]);
            }
            $this->schedules->update($locked, $data);
        });
    }

    public function delete(Course $course, Schedule $schedule): void
    {
        DB::transaction(function () use ($course, $schedule): void {
            $lockedCourse = $this->courses->lock($course->id);
            $locked = $this->schedules->lock($schedule->id);
            if ($this->schedules->participantCount($locked->id) > 0) {
                throw ValidationException::withMessages(['schedule' => 'Jadwal dengan pengguna terdaftar tidak dapat dihapus.']);
            }
            if ($lockedCourse->status === 'publish' && $this->courses->scheduleCount($lockedCourse->id) <= 1) {
                throw ValidationException::withMessages(['schedule' => 'Kelas publikasi harus memiliki minimal satu jadwal. Ubah kelas menjadi draft terlebih dahulu.']);
            }
            $this->schedules->delete($locked);
        });
    }

    public function participantCount(Schedule $schedule): int
    {
        return $this->schedules->participantCount($schedule->id);
    }

    private function time(?string $value): ?string
    {
        return $value ? \Carbon\Carbon::parse($value)->format('H:i') : null;
    }
}
