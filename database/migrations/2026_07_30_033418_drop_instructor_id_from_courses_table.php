<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('courses') || ! Schema::hasColumn('courses', 'instructor_id')) {
            return;
        }

        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign(['instructor_id']);
            $table->dropColumn('instructor_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('courses') || Schema::hasColumn('courses', 'instructor_id')) {
            return;
        }

        Schema::table('courses', function (Blueprint $table) {
            $table->foreignUlid('instructor_id')
                ->nullable()
                ->after('category_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }
};
