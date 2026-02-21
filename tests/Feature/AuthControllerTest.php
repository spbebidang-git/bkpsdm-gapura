<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Management\UserMeta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Roles
        Role::create(['name' => 'Admin']);
        Role::create(['name' => 'Instructor']);
        Role::create(['name' => 'Participant']);

        // 2. Definisikan Route Dummy agar route() tidak error
        Route::get('/', function () {
            return 'home';
        })->name('home');
        Route::get('/admin/dashboard', function () {
            return 'admin';
        })->name('admin.dashboard');
        Route::get('/instructor/dashboard', function () {
            return 'instructor';
        })->name('instructor.dashboard');
        Route::get('/login', function () {
            return 'login';
        })->name('sign-in.index');

        // Tambahkan ini untuk mencegah error "dashboard not defined" dari middleware Laravel
        Route::get('/dashboard', function () {
            return 'dashboard';
        })->name('dashboard');

        config(['services.sso.base_url' => 'https://sso.test']);
    }

    private function postInertia($url, $data = [])
    {
        return $this->post($url, $data, [
            'X-Inertia' => 'true',
            'X-Requested-With' => 'XMLHttpRequest'
        ]);
    }

    public function test_admin_can_login_via_email_and_redirects_via_inertia_location()
    {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => Hash::make('password123')
        ]);
        $admin->assignRole('Admin');

        // Pastikan endpoint '/login' ini memanggil AuthController@store milik Anda
        $response = $this->postInertia(route('sign-in.store'), [
            'username' => 'admin@test.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(409);
        $response->assertHeader('X-Inertia-Location', route('admin.dashboard'));
    }

    public function test_participant_can_login_locally_using_nip()
    {
        $user = User::factory()->create(['email' => 'peserta@test.com']);
        $user->assignRole('Participant');

        // Sesuaikan nama tabel 'user_metas' atau 'user_meta'
        UserMeta::create(['user_id' => $user->id, 'key' => 'nip', 'value' => '12345']);
        UserMeta::create(['user_id' => $user->id, 'key' => 'status_pegawai', 'value' => 'PNS']);

        $response = $this->post(route('sign-in.store'), [
            'username' => '12345',
            'password' => 'password'
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_participant_triggers_sso_sync_if_status_pegawai_is_missing()
    {
        $user = User::factory()->create(['email' => 'peserta@test.com']);
        $user->assignRole('Participant');
        UserMeta::create(['user_id' => $user->id, 'key' => 'nip', 'value' => '12345']);

        Http::fake([
            '*/oauth/token' => Http::response(['access_token' => 'fake-token'], 200),
            '*/api/get_nip_data' => Http::response([
                'fv_nama' => 'Nama Baru',
                'fv_email' => 'peserta@test.com',
                'status_pegawai' => 'PPPK'
            ], 200),
        ]);

        $this->post(route('sign-in.store'), [
            'username' => '12345',
            'password' => 'password'
        ]);

        // Ganti 'user_metas' jika nama tabel Anda berbeda
        $this->assertDatabaseHas('user_metas', [
            'user_id' => $user->id,
            'key' => 'status_pegawai',
            'value' => 'PPPK'
        ]);
    }

    public function test_new_user_provisioning_via_sso()
    {
        Http::fake([
            '*/oauth/token' => Http::response(['access_token' => 'token'], 200),
            '*/api/get_nip_data' => Http::response([
                'fv_nama' => 'User Baru',
                'fv_email' => 'baru@test.com',
                'status_pegawai' => 'Tetap'
            ], 200),
        ]);

        $response = $this->post(route('sign-in.store'), [
            'username' => '999',
            'password' => 'password'
        ]);

        $this->assertDatabaseHas('users', ['email' => 'baru@test.com']);
        $this->assertAuthenticated();
    }
}
