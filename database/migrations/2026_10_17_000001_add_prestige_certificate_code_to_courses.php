<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('prestige_certificate_code')->nullable()->unique();
        });
        Schema::table('learns', function (Blueprint $table) {
            $table->timestamp('prestige_certificate_registered_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('learns', function (Blueprint $table) {
            $table->dropColumn('prestige_certificate_registered_at');
        });
        Schema::table('courses', function (Blueprint $table) {
            $table->dropUnique(['prestige_certificate_code']);
            $table->dropColumn('prestige_certificate_code');
        });
    }
};
