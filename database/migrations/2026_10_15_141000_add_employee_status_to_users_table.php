<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'employee_status_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignUlid('employee_status_id')->nullable()->after('opd_id')->constrained('employee_status')->nullOnDelete();
            });
        }

        foreach (DB::table('employee_status')->whereNull('deleted_at')->get(['id', 'title']) as $status) {
            $userIds = DB::table('user_meta')
                ->where('key', 'status_pegawai')
                ->whereRaw('upper(trim(value)) = ?', [mb_strtoupper(trim($status->title))])
                ->pluck('user_id');

            if ($userIds->isNotEmpty()) {
                DB::table('users')->whereIn('id', $userIds)->whereNull('employee_status_id')->update(['employee_status_id' => $status->id]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'employee_status_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('employee_status_id');
            });
        }
    }
};
