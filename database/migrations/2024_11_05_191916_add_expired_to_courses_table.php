<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->integer('expired')->after('outline_topic')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('learn_items', function (Blueprint $table) {
            $table->dropColumn('expired');
        });
    }
};
