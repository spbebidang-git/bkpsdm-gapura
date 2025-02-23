<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_variants', function (Blueprint $table) {
            $table->string('code')->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('question_variants', function (Blueprint $table) {
            $table->drop('code');
        });
    }
};
