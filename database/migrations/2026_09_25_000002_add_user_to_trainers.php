<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('trainers', 'user_id')) {
            Schema::table('trainers', function (Blueprint $table) {
                $table->ulid('user_id')->nullable()->unique()->after('id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('trainers', 'user_id')) {
            Schema::table('trainers', function (Blueprint $table) {
                $table->dropUnique(['user_id']);
                $table->dropColumn('user_id');
            });
        }
    }
};
