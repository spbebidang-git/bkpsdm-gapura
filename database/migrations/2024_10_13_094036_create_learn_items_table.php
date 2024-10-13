<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learn_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learn_id')->constrained('learns')->cascadeOnDelete();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->string('title');
            $table->enum('type', ['pre-test', 'post-test', 'task', 'meeting', 'video', 'literature']);
            $table->longText('response')->nullable();
            $table->enum('status', ['available', 'in_progress', 'done']);
            $table->timestamps();
        });
    }
};
