<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->integer('progress')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->timestamps();
        });
    }
};
