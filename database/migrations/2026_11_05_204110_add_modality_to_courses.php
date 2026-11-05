<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('courses', 'modality')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->string('modality', 10)->default('lms')->index()->after('access_mode');
            });
        }

        // Backfill: public lama -> KMS, private lama -> LMS.
        $kmsCourseIds = DB::table('courses')->where('visibility', 'public')->pluck('id');

        if ($kmsCourseIds->isNotEmpty()) {
            DB::table('courses')->whereIn('id', $kmsCourseIds)->update([
                'modality' => 'kms',
                'visibility' => 'public',
                'access_mode' => 'open',
                'jam_pelajaran' => 0,
            ]);

            // Rapikan KMS lama: kegiatan non-literature/video dihapus.
            // FK learn_items.activity_id cascade menghapus item belajar terkait.
            DB::table('activities')
                ->whereIn('course_id', $kmsCourseIds)
                ->whereNotIn('type', ['literature', 'video'])
                ->delete();
        }
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('modality');
        });
    }
};
