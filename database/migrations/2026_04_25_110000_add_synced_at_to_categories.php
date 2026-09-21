<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('categories', 'synced_at')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->timestamp('synced_at')->nullable()->after('title');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('categories', 'synced_at')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn('synced_at');
            });
        }
    }
};
