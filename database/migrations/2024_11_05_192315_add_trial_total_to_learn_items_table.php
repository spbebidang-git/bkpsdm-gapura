<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learn_items', function (Blueprint $table) {
            $table->tinyInteger('trial_total')->after('response')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('learn_items', function (Blueprint $table) {
            $table->dropColumn('trial_total');
        });
    }
};
