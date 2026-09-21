<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * enrollments = access gate. schedule_user kept for BC.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('enrollments')) {
            Schema::create('enrollments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('participant_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->foreignId('schedule_id')->nullable()->constrained('schedules')->nullOnDelete();
                $table->enum('status', ['active', 'completed', 'expired', 'cancelled'])->default('active');
                $table->dateTime('enrolled_at');
                $table->dateTime('access_starts_at');
                $table->dateTime('access_ends_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['participant_id', 'status']);
                $table->index(['course_id', 'status']);
                $table->index(['course_id', 'schedule_id']);
                $table->index(['access_ends_at']);
                $table->index(['schedule_id']);
            });
        }

        $this->backfillEnrollments();

        if (Schema::hasTable('learns') && ! Schema::hasColumn('learns', 'enrollment_id')) {
            Schema::table('learns', function (Blueprint $table) {
                $table->unsignedBigInteger('enrollment_id')->nullable()->after('id');
            });

            $this->linkLearns();

            Schema::table('learns', function (Blueprint $table) {
                $table->foreign('enrollment_id')->references('id')->on('enrollments')->nullOnDelete();
            });

            if (! $this->indexExists('learns', 'learns_enrollment_id_unique')) {
                Schema::table('learns', function (Blueprint $table) {
                    $table->unique('enrollment_id');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('learns') && Schema::hasColumn('learns', 'enrollment_id')) {
            Schema::table('learns', function (Blueprint $table) {
                $table->dropForeign(['enrollment_id']);
                if ($this->indexExists('learns', 'learns_enrollment_id_unique')) {
                    $table->dropUnique(['enrollment_id']);
                }
                $table->dropColumn('enrollment_id');
            });
        }

        Schema::dropIfExists('enrollments');
    }

    private function backfillEnrollments(): void
    {
        if (! Schema::hasTable('schedule_user') || DB::table('enrollments')->exists()) {
            return;
        }

        $now = now();
        $rows = DB::table('schedule_user as su')
            ->join('schedules as s', 's.id', '=', 'su.schedule_id')
            ->join('courses as c', 'c.id', '=', 's.course_id')
            ->select([
                'su.participant_id',
                'su.schedule_id',
                'su.created_at as su_created',
                's.course_id',
                's.access_from',
                's.access_to',
                's.date',
                's.time_from',
                's.time_to',
                'c.access_mode',
                'c.default_access_hours',
            ])
            ->get();

        foreach ($rows as $row) {
            $enrolledAt = $row->su_created ?? $now;
            $isScheduled = ($row->access_mode ?? '') === 'scheduled';
            $scheduleId = $isScheduled ? $row->schedule_id : null;

            if ($isScheduled) {
                $starts = $row->access_from;
                $ends = $row->access_to;
                if (! $starts && $row->date) {
                    $d = substr((string) $row->date, 0, 10);
                    $tf = $row->time_from ? substr((string) $row->time_from, 0, 8) : '00:00:00';
                    $tt = $row->time_to ? substr((string) $row->time_to, 0, 8) : '23:59:59';
                    $starts = "{$d} {$tf}";
                    $ends = "{$d} {$tt}";
                }
                $starts = $starts ?: $enrolledAt;
            } else {
                $starts = $enrolledAt;
                $hours = $row->default_access_hours;
                $ends = $hours
                    ? date('Y-m-d H:i:s', strtotime((string) $enrolledAt) + ((int) $hours * 3600))
                    : null;
            }

            $q = DB::table('enrollments')
                ->where('participant_id', $row->participant_id)
                ->where('course_id', $row->course_id);
            if ($scheduleId) {
                $q->where('schedule_id', $scheduleId);
            } else {
                $q->whereNull('schedule_id');
            }
            if ($q->exists()) {
                continue;
            }

            DB::table('enrollments')->insert([
                'participant_id' => $row->participant_id,
                'course_id' => $row->course_id,
                'schedule_id' => $scheduleId,
                'status' => 'active',
                'enrolled_at' => $enrolledAt,
                'access_starts_at' => $starts,
                'access_ends_at' => $ends,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // orphan learns
        $orphans = DB::table('learns as l')
            ->leftJoin('enrollments as e', function ($join) {
                $join->on('e.participant_id', '=', 'l.participant_id')
                    ->on('e.course_id', '=', 'l.course_id');
            })
            ->whereNull('e.id')
            ->select('l.participant_id', 'l.course_id', 'l.created_at')
            ->distinct()
            ->get();

        foreach ($orphans as $row) {
            $course = DB::table('courses')->where('id', $row->course_id)->first();
            $enrolledAt = $row->created_at ?? $now;
            $hours = $course->default_access_hours ?? null;
            $ends = $hours
                ? date('Y-m-d H:i:s', strtotime((string) $enrolledAt) + ((int) $hours * 3600))
                : null;

            DB::table('enrollments')->insert([
                'participant_id' => $row->participant_id,
                'course_id' => $row->course_id,
                'schedule_id' => null,
                'status' => 'active',
                'enrolled_at' => $enrolledAt,
                'access_starts_at' => $enrolledAt,
                'access_ends_at' => $ends,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function linkLearns(): void
    {
        DB::table('learns')->orderBy('id')->chunkById(200, function ($learns) {
            foreach ($learns as $learn) {
                if ($learn->enrollment_id) {
                    continue;
                }

                $enrollmentId = DB::table('enrollments')
                    ->where('participant_id', $learn->participant_id)
                    ->where('course_id', $learn->course_id)
                    ->orderBy('id')
                    ->value('id');

                if (! $enrollmentId) {
                    continue;
                }

                $taken = DB::table('learns')->where('enrollment_id', $enrollmentId)->exists();
                if ($taken) {
                    continue;
                }

                DB::table('learns')->where('id', $learn->id)->update(['enrollment_id' => $enrollmentId]);
            }
        });
    }

    private function indexExists(string $table, string $name): bool
    {
        $db = Schema::getConnection()->getDatabaseName();
        $row = DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$db, $table, $name]
        );

        return $row && (int) $row->c > 0;
    }
};
