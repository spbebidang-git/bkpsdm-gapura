<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Preserve the original access window; never start a new window at migration time.
        DB::table('schedule_user as su')->join('schedules as s', 's.id', '=', 'su.schedule_id')
            ->join('courses as c', 'c.id', '=', 's.course_id')
            ->select('su.participant_id', 'su.schedule_id', 'su.created_at as registered_at', 's.course_id',
                's.access_from', 's.access_to', 's.date', 's.time_from', 's.time_to',
                'c.access_mode', 'c.default_access_hours', 'c.created_at as course_created_at')
            ->orderBy('su.schedule_id')->orderBy('su.participant_id')->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    $scheduleId = $row->access_mode === 'scheduled' ? $row->schedule_id : null;
                    $enrollment = DB::table('enrollments')->where('participant_id', $row->participant_id)
                        ->where('course_id', $row->course_id)->where('schedule_id', $scheduleId)->first();
                    $learn = DB::table('learns')->where('participant_id', $row->participant_id)
                        ->where('course_id', $row->course_id)->first();
                    if (! $enrollment) {
                        $registeredAt = Carbon::parse($row->registered_at ?? $learn?->created_at ?? $row->course_created_at);
                        $starts = $registeredAt;
                        $ends = $row->default_access_hours ? $registeredAt->copy()->addHours((int) $row->default_access_hours) : null;
                        if ($scheduleId) {
                            $starts = $row->access_from ?: ($row->date ? $row->date.' '.($row->time_from ?: '00:00:00') : $registeredAt);
                            $ends = $row->access_to ?: ($row->date ? $row->date.' '.($row->time_to ?: '23:59:59') : null);
                        }
                        $enrollmentId = (string) Str::ulid();
                        DB::table('enrollments')->insert([
                            'id' => $enrollmentId, 'participant_id' => $row->participant_id,
                            'course_id' => $row->course_id, 'schedule_id' => $scheduleId,
                            'status' => $learn?->finished_at ? 'completed' : 'active',
                            'enrolled_at' => $registeredAt, 'access_starts_at' => $starts, 'access_ends_at' => $ends,
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                    } else {
                        $enrollmentId = $enrollment->id;
                    }
                    if ($learn && ! $learn->enrollment_id) {
                        DB::table('learns')->where('id', $learn->id)->update(['enrollment_id' => $enrollmentId]);
                    }
                }
            });
    }

    public function down(): void
    {
        // These records preserve historical registration and must survive rollback.
    }
};
