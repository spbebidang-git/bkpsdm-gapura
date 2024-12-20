<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->text('teaser_video')->nullable()->change(); // Mengubah kolom value menjadi nullable
        });
    }

    public function down()
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->text('teaser_video')->nullable(false)->change(); // Mengubah kembali jika ingin rollback
        });
    }
};
