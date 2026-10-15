<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('participant_notifications')) {
            Schema::create('participant_notifications', function (Blueprint $table) {
                $table->ulid('id')->primary();
                $table->foreignUlid('participant_id')->constrained('users')->cascadeOnDelete();
                $table->string('event_key', 160);
                $table->string('type', 40);
                $table->json('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                $table->unique(['participant_id', 'event_key'], 'pn_participant_event_uq');
                $table->index(['participant_id', 'created_at'], 'pn_participant_created_idx');
                $table->index(['participant_id', 'read_at', 'created_at'], 'pn_participant_read_created_idx');
                $table->index('created_at', 'pn_created_idx');
            });
        } else {
            Schema::table('participant_notifications', function (Blueprint $table) {
                if (! $this->indexExists('pn_participant_read_created_idx')) $table->index(['participant_id', 'read_at', 'created_at'], 'pn_participant_read_created_idx');
                if (! $this->indexExists('pn_created_idx')) $table->index('created_at', 'pn_created_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('participant_notifications');
    }

    private function indexExists(string $name): bool
    {
        $database = Schema::getConnection()->getDatabaseName();
        return (bool) \Illuminate\Support\Facades\DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, 'participant_notifications', $name],
        )?->c;
    }
};
