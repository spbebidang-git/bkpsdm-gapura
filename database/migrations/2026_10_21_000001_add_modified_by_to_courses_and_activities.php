<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->foreignUlid('modified_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->foreignUlid('modified_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('activities', fn (Blueprint $table) => $table->dropConstrainedForeignId('modified_by'));
        Schema::table('courses', fn (Blueprint $table) => $table->dropConstrainedForeignId('modified_by'));
    }
};
