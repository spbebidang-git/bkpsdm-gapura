<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scorms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->string('sub_title')->nullable();
            $table->string('folder');              // folder hasil ekstrak paket
            $table->string('launch_url');          // file peluncur (mis. index_lms.html)
            $table->string('version')->nullable(); // 1.2 / 2004
            $table->integer('duration')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scorms');
    }
};
