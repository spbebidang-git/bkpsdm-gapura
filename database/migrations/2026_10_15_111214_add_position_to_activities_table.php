<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->integer('position')->default(0)->after('value');
        });

        // Backfill: per course, urutkan sesuai id lalu beri position 10,20,30...
        // (jarak 10 agar bisa menyisipkan aktivitas di antaranya).
        $courseIds = DB::table('activities')->distinct()->pluck('course_id');
        foreach ($courseIds as $courseId) {
            $ids = DB::table('activities')
                ->where('course_id', $courseId)
                ->orderBy('id')
                ->pluck('id');
            $pos = 10;
            foreach ($ids as $id) {
                DB::table('activities')->where('id', $id)->update(['position' => $pos]);
                $pos += 10;
            }
        }
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
