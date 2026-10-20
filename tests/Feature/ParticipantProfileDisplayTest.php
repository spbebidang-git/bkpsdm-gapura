<?php

namespace Tests\Feature;

use App\Models\Identity\EmployeeStatus;
use App\Models\Identity\Opd;
use App\Models\Identity\User;
use App\Models\Identity\UserMeta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ParticipantProfileDisplayTest extends TestCase
{
    use RefreshDatabase;

    private function participant(array $attributes = []): User
    {
        Role::findOrCreate('participant', 'web');
        $user = User::factory()->create($attributes);
        $user->assignRole('participant');

        return $user;
    }

    public function test_profile_displays_canonical_identity_opd_and_employee_status(): void
    {
        $opd = Opd::create(['code' => '001', 'name' => 'Dinas Komunikasi', 'is_active' => true]);
        $status = EmployeeStatus::create(['title' => 'PNS']);
        $user = $this->participant(['name' => 'Peserta Gapura', 'email' => 'peserta@example.test',
            'nip' => '199004102022032002', 'opd_id' => $opd->id, 'employee_status_id' => $status->id]);
        foreach (['nip' => '11111', 'status_pegawai' => 'PPPK', 'fv_nama_uker' => 'Unit lama'] as $key => $value) {
            UserMeta::create(['user_id' => $user->id, 'key' => $key, 'value' => $value]);
        }
        $this->actingAs($user)->get('/profile')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Participant/Profile/Index')
            ->where('profile.name', 'Peserta Gapura')->where('profile.email', 'pexxxxta@example.test')
            ->where('profile.nip', '199xxxx002')->where('profile.unit_kerja', 'Dinas Komunikasi')
            ->where('profile.status_pegawai', 'PNS')->missing('profile.access_token'));
    }

    public function test_legacy_profile_uses_encrypted_nip_and_metadata_fallbacks(): void
    {
        $user = $this->participant(['nip' => null]);
        foreach (['nip' => Crypt::encryptString('199004102022032002'), 'status_pegawai' => ' PPPK ', 'fv_nama_uker' => ' Badan Kepegawaian '] as $key => $value) {
            UserMeta::create(['user_id' => $user->id, 'key' => $key, 'value' => $value]);
        }
        $this->actingAs($user)->get('/profile')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('profile.nip', '199xxxx002')->where('profile.unit_kerja', 'Badan Kepegawaian')
            ->where('profile.status_pegawai', 'PPPK'));
    }

    public function test_missing_identity_fields_are_null_for_the_unavailable_placeholder(): void
    {
        $user = $this->participant(['nip' => null, 'email' => ' ']);
        $this->actingAs($user)->get('/profile')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('profile.email', null)->where('profile.nip', null)
            ->where('profile.unit_kerja', null)->where('profile.status_pegawai', null));
    }
}
