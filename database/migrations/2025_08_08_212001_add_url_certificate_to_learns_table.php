<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learns', function (Blueprint $table) {
            $table->string('url_certificate')->nullable()->after('finished_at');
        });
    }

    public function down(): void
    {
        Schema::table('learns', function (Blueprint $table) {
            $table->dropColumn('url_certificate');
        });
    }
};
