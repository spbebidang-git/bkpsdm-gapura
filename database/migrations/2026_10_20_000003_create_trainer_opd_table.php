<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trainer_opd', function (Blueprint $table) {
            $table->ulid('trainer_id');
            $table->foreignId('opd_id')->constrained('opds')->cascadeOnDelete();
            $table->primary(['trainer_id', 'opd_id']);
            $table->foreign('trainer_id')->references('id')->on('trainers')->cascadeOnDelete();
        });

        DB::table('trainers')->orderBy('id')->get()->each(function ($trainer): void {
            $opdId = $trainer->opd_id;
            if ($trainer->user_id) {
                $opdId = DB::table('users')->where('id', $trainer->user_id)->value('opd_id') ?? $opdId;
            }
            if ($opdId && DB::table('opds')->where('id', $opdId)->exists()) {
                DB::table('trainer_opd')->insertOrIgnore(['trainer_id' => $trainer->id, 'opd_id' => $opdId]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_opd');
    }
};
