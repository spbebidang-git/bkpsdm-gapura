<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questionnaire_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questionnaire_id')->constrained('questionnaires')->cascadeOnDelete();
            // yesno|text|essay|radio|check|drop|rate|date|numeric|section
            // (mengikuti nama tipe mod_questionnaire)
            $table->string('type', 20);
            $table->text('content');
            $table->boolean('is_required')->default(false);
            $table->integer('position')->default(0);
            // text/numeric: batas karakter/digit. rate: jumlah tingkat skala.
            $table->integer('length')->default(0);
            $table->timestamps();

            $table->index(['questionnaire_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questionnaire_questions');
    }
};
