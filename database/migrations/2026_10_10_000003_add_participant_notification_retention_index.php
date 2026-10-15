<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('participant_notifications') && ! $this->indexExists()) {
            Schema::table('participant_notifications', function (Blueprint $table) {
                $table->index('created_at', 'pn_created_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('participant_notifications')) {
            Schema::table('participant_notifications', function (Blueprint $table) {
                $table->dropIndex('pn_created_idx');
            });
        }
    }

    private function indexExists(): bool
    {
        $database = Schema::getConnection()->getDatabaseName();
        return (bool) \Illuminate\Support\Facades\DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, 'participant_notifications', 'pn_created_idx'],
        )?->c;
    }
};
