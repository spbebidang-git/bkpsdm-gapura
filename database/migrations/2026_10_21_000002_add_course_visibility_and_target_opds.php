<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('visibility', 10)->default('private')->index();
        });
        Schema::create('course_opd', function (Blueprint $table) {
            $table->foreignUlid('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('opd_id')->constrained('opds')->cascadeOnDelete();
            $table->primary(['course_id', 'opd_id']);
        });
        DB::table('courses')->whereNull('opd_id')->update(['visibility' => 'public']);
        DB::table('courses')->whereNotNull('opd_id')->orderBy('id')->chunk(500, function ($courses) {
            DB::table('course_opd')->insert($courses->map(fn ($course) => [
                'course_id' => $course->id, 'opd_id' => $course->opd_id,
            ])->all());
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_opd');
        Schema::table('courses', fn (Blueprint $table) => $table->dropColumn('visibility'));
    }
};
