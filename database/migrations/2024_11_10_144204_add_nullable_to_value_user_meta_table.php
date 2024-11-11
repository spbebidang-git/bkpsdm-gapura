<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('user_meta', function (Blueprint $table) {
            $table->text('value')->nullable()->change(); // Mengubah kolom value menjadi nullable
        });
    }

    public function down()
    {
        Schema::table('user_meta', function (Blueprint $table) {
            $table->text('value')->nullable(false)->change(); // Mengubah kembali jika ingin rollback
        });
    }
};
