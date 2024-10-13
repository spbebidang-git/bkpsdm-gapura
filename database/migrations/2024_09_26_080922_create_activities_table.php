<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->enum('type', ['pre-test', 'post-test', 'task', 'video', 'meeting', 'literature']);
            $table->string('title');
            $table->integer('timer')->nullable();
            $table->integer('value')->default(0);
            $table->timestamps();
        });
    }
};
