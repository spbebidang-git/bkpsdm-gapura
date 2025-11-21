<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('helps', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->text('answer');
            $table->boolean('is_active')->default(true); // untuk menampilkan di frontend
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('helps');
    }
};
