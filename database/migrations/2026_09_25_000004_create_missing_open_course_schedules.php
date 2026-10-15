<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Give legacy open courses the empty schedule required for enrolment.
     */
    public function up(): void
    {
        DB::table('courses')
            ->where('access_mode', 'open')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('schedules')
                    ->whereColumn('schedules.course_id', 'courses.id')
                    ->whereNull('schedules.deleted_at');
            })
            ->orderBy('id')
            ->select('id')
            ->each(function (object $course): void {
                $now = now();

                DB::table('schedules')->insert([
                    'id' => (string) Str::ulid(),
                    'course_id' => $course->id,
                    'date' => null,
                    'time_from' => null,
                    'time_to' => null,
                    'quota' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }

    public function down(): void
    {
        // Schedules may already hold participant enrolments; keep them on rollback.
    }
};
