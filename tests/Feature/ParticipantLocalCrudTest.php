<?php

namespace Tests\Feature;

use App\Models\Identity\User;
use App\Models\Identity\EmployeeStatus;
use App\Models\Identity\Opd;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ParticipantLocalCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['super_admin', 'participant', 'administrator'] as $role) {
            Role::findOrCreate($role, 'web');
        }
    }

    public function test_admin_can_create_and_update_local_participant(): void
    {
        $admin = User::factory()->create(['auth_source' => User::AUTH_SOURCE_LOCAL]);
        $admin->assignRole('super_admin');
        $status = EmployeeStatus::create(['title' => 'PNS']);
        $opd = Opd::create(['code' => 'DIDIK', 'name' => 'Dinas Pendidikan', 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.participant.store'), [
            'name' => 'Peserta Lokal',
            'email' => 'peserta@example.test',
            'nip' => '123456789',
            'employee_status_id' => $status->id,
            'opd_id' => $opd->id,
            'jabatan' => 'Analis',
            'password' => 'password-lokal',
            'password_confirmation' => 'password-lokal',
        ])->assertRedirect(route('admin.participant.index'));

        $participant = User::where('email', 'peserta@example.test')->firstOrFail();
        $this->assertSame(User::AUTH_SOURCE_LOCAL, $participant->auth_source);
        $this->assertTrue($participant->hasRole('participant'));
        $this->assertSame('PNS', $participant->metas()->where('key', 'status_pegawai')->value('value'));
        $this->assertSame($status->id, $participant->employee_status_id);
        $this->assertSame($opd->id, $participant->opd_id);

        $this->actingAs($admin)->put(route('admin.participant.update', $participant), [
            'name' => 'Peserta Diperbarui',
            'email' => 'peserta@example.test',
            'nip' => '123456789',
            'employee_status_id' => '',
            'opd_id' => '',
            'jabatan' => 'Pranata',
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('admin.participant.index'));

        $this->assertSame('Peserta Diperbarui', $participant->fresh()->name);
        $this->assertNull($participant->fresh()->employee_status_id);
        $this->assertNull($participant->fresh()->opd_id);
        $this->assertNull($participant->metas()->where('key', 'status_pegawai')->value('value'));
    }

    public function test_deleting_participant_preserves_other_roles_and_external_users_are_read_only(): void
    {
        $admin = User::factory()->create(['auth_source' => User::AUTH_SOURCE_LOCAL]);
        $admin->assignRole('super_admin');
        $multiRole = User::factory()->create(['auth_source' => User::AUTH_SOURCE_LOCAL]);
        $multiRole->assignRole(['participant', 'administrator']);
        $external = User::factory()->create(['auth_source' => User::AUTH_SOURCE_SSO]);
        $external->assignRole('participant');

        $this->actingAs($admin)->delete(route('admin.participant.destroy', $multiRole))
            ->assertRedirect(route('admin.participant.index'));

        $this->assertFalse($multiRole->fresh()->hasRole('participant'));
        $this->assertTrue($multiRole->fresh()->hasRole('administrator'));
        $this->actingAs($admin)->get(route('admin.participant.edit', $external))->assertNotFound();
        $this->actingAs($admin)->delete(route('admin.participant.destroy', $external))->assertNotFound();
    }
}
