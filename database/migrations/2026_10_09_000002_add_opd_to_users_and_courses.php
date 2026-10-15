<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'opd_id')) {
                $table->foreignId('opd_id')->nullable()->after('nip')->constrained('opds')->nullOnDelete();
            }
        });

        Schema::table('courses', function (Blueprint $table) {
            if (! Schema::hasColumn('courses', 'opd_id')) {
                $table->foreignId('opd_id')->nullable()->after('category_id')->constrained('opds')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'opd_id')) {
                $table->dropConstrainedForeignId('opd_id');
            }
        });
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'opd_id')) {
                $table->dropConstrainedForeignId('opd_id');
            }
        });
    }
};
