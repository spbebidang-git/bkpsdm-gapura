<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('categories')) {
            return;
        }

        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'slug')) {
                // drop unique index + column (Laravel drops index with column on most drivers)
                $table->dropColumn('slug');
            }
            if (Schema::hasColumn('categories', 'is_primary')) {
                $table->dropColumn('is_primary');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('categories')) {
            return;
        }

        Schema::table('categories', function (Blueprint $table) {
            if (! Schema::hasColumn('categories', 'slug')) {
                $table->string('slug')->nullable()->unique();
            }
            if (! Schema::hasColumn('categories', 'is_primary')) {
                $table->tinyInteger('is_primary')->default(0);
            }
        });
    }
};
