<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('enrollments') || ! Schema::hasTable('participant_notifications')) return;

        $now = now();
        DB::table('enrollments')->where('status', 'active')->whereNotNull('access_ends_at')
            ->where('access_ends_at', '<=', $now)
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')->from('learns')->whereColumn('learns.enrollment_id', 'enrollments.id')->whereNotNull('learns.finished_at');
            })->orderBy('id')->chunkById(200, function ($enrollments) use ($now) {
                foreach ($enrollments as $enrollment) {
                    $deadline = $now->copy()->addDays(7);
                    DB::transaction(function () use ($enrollment, $now, $deadline) {
                        DB::table('enrollments')->where('id', $enrollment->id)->update([
                            'access_ends_at' => $deadline,
                            'updated_at' => $now,
                        ]);
                        $course = DB::table('courses')->where('id', $enrollment->course_id)->first(['title', 'slug']);
                        if (! $course) return;
                        $eventKey = 'enrollment:'.$enrollment->id.':grace';
                        DB::table('participant_notifications')->insertOrIgnore([
                            'id' => (string) Str::ulid(),
                            'participant_id' => $enrollment->participant_id,
                            'event_key' => $eventKey,
                            'type' => 'grace',
                            'data' => json_encode([
                                'title' => 'Masa transisi akses kelas',
                                'message' => 'Anda mendapat waktu tambahan untuk menyelesaikan '.$course->title.'.',
                                'course_id' => $enrollment->course_id,
                                'course_slug' => $course->slug,
                                'enrollment_id' => $enrollment->id,
                                'deadline_at' => $deadline->toISOString(),
                                'occurred_at' => $now->toISOString(),
                            ], JSON_THROW_ON_ERROR),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    });
                }
            });
    }

    public function down(): void
    {
        // Grace access and notices are user data; rolling back code must not revoke access.
    }
};
