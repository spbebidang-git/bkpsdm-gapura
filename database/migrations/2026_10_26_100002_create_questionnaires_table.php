<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questionnaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->string('sub_title')->nullable();
            $table->text('intro')->nullable();      // pengantar di atas daftar pertanyaan
            $table->boolean('is_anonymous')->default(false); // identitas disembunyikan di rekap
            $table->integer('duration')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questionnaires');
    }
};
