<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learns', function (Blueprint $table) {
            $table->boolean('is_post_certificate')->after('finished_at');
        });
    }

    public function down(): void
    {
        Schema::table('learns', function (Blueprint $table) {
            $table->dropColumn('is_post_certificate');
        });
    }
};
