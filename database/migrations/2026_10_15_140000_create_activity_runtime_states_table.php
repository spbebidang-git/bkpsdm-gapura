<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_runtime_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learn_id')->constrained('learns')->cascadeOnDelete();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->string('type', 16);
            $table->longText('state')->nullable();
            $table->string('progress_status', 32)->nullable();
            $table->decimal('score_raw', 10, 2)->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();

            $table->unique(['learn_id', 'activity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_runtime_states');
    }
};
