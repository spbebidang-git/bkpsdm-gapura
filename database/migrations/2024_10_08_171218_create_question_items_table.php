<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_variant_id')->constrained('question_variants')->onDelete('cascade');
            $table->text('description');
            $table->integer('score');
            $table->timestamps();
        });
    }
};
