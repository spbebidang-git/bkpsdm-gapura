<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->text('description');
            $table->enum('type', ['pre', 'post']);
            $table->integer('min_score')->default(0);
            $table->tinyInteger('is_loop')->default(0);
            $table->boolean('show_result')->default(false);
            $table->timestamps();
        });
    }
};
