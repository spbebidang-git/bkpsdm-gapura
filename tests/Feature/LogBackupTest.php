<?php

namespace Tests\Feature;

use App\Models\Identity\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LogBackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_logs_backup_creates_zip(): void
    {
        File::ensureDirectoryExists(storage_path('logs'));
        File::put(storage_path('logs/test-backup-smoke.log'), "line\n");

        $code = Artisan::call('logs:backup');
        $this->assertSame(0, $code);

        $zip = storage_path('app/backups/logs/logs-'.now()->format('Y-m-d').'.zip');
        $this->assertFileExists($zip);

        @unlink(storage_path('logs/test-backup-smoke.log'));
    }

    public function test_admin_can_view_log_page(): void
    {
        Role::findOrCreate('Admin');
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $this->actingAs($admin)
            ->get(route('admin.management.log.index'))
            ->assertOk();
    }
}
