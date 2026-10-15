<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_message_templates', function (Blueprint $table) {
            $table->string('event_key', 60)->primary();
            $table->string('title', 160);
            $table->text('message');
            $table->timestamps();
        });

        $now = now();
        foreach (config('notification_messages', []) as $eventKey => $template) {
            DB::table('notification_message_templates')->insert([
                'event_key' => $eventKey,
                'title' => $template['title'],
                'message' => $template['message'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('permissions')->insertOrIgnore([
            'name' => 'notification-template.manage',
            'guard_name' => 'web',
        ]);

        $roleId = DB::table('roles')->where('name', 'super_admin')->where('guard_name', 'web')->value('id');
        $permissionId = DB::table('permissions')
            ->where('name', 'notification-template.manage')
            ->where('guard_name', 'web')
            ->value('id');

        if ($roleId && $permissionId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_message_templates');
        DB::table('permissions')
            ->where('name', 'notification-template.manage')
            ->where('guard_name', 'web')
            ->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
