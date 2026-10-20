<?php

namespace Tests\Feature;

use App\Models\Course\Trainer;
use App\Models\Identity\Opd;
use App\Models\Identity\User;
use App\Models\Identity\UserMeta;
use App\Services\Course\TrainerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TrainerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['super_admin', 'trainer', 'participant'] as $role) {
            Role::findOrCreate($role, 'web');
        }
        $admin = User::factory()->create(['auth_source' => 'local']);
        $admin->assignRole('super_admin');
        $this->actingAs($admin);
    }

    private function trainerOpd(): int
    {
        return Opd::firstOrCreate(['code' => 'TRAINER-LOCAL'], ['name' => 'Dinas Pendidikan', 'is_active' => true])->id;
    }

    private function ssoUser(bool $active = true): User
    {
        $opd = Opd::create(['code' => fake()->unique()->numerify('OPD-####'), 'name' => 'OPD pengujian', 'is_active' => $active]);
        return User::factory()->create(['auth_source' => 'sso', 'opd_id' => $opd->id]);
    }

    public function test_local_trainer_uses_email_as_login_username(): void
    {
        $count = User::count();
        $this->post(route('admin.trainer.store'), ['source' => 'local', 'opd_id' => $this->trainerOpd(), 'name' => 'Trainer Lokal', 'jabatan' => 'Dosen',
            'email' => 'trainer@example.test', 'password' => 'secret123', 'password_confirmation' => 'secret123'])
            ->assertRedirect(route('admin.trainer.index'));
        $trainer = Trainer::where('name', 'Trainer Lokal')->firstOrFail();
        $this->assertNotNull($trainer->user_id);
        $this->assertSame($count + 1, User::count());
        $this->assertTrue($trainer->user->hasRole('trainer'));
        $this->assertTrue(\Illuminate\Support\Facades\Auth::attempt(['email' => 'trainer@example.test', 'password' => 'secret123', 'auth_source' => 'local']));
        $this->actingAs(User::role('super_admin')->firstOrFail());
        $this->put(route('admin.trainer.update', $trainer), ['source' => 'user', 'opd_id' => $this->trainerOpd(), 'name' => 'Nama baru', 'jabatan' => 'Pelatih',
            'email' => 'trainer@example.test'])
            ->assertRedirect(route('admin.trainer.index'));
        $this->assertSame('Nama baru', $trainer->fresh()->name);
        $this->delete(route('admin.trainer.destroy', $trainer))->assertRedirect(route('admin.trainer.index'));
        $this->assertSoftDeleted($trainer);
    }

    public function test_local_validation_uses_indonesian_messages(): void
    {
        $this->postJson(route('admin.trainer.store'), ['source' => 'local', 'opd_id' => $this->trainerOpd(), 'name' => '', 'photo' => UploadedFile::fake()->create('invalid.txt', 2049, 'text/plain')])
            ->assertUnprocessable()->assertJsonValidationErrors(['name', 'photo'])
            ->assertJsonPath('errors.name.0', 'Nama trainer wajib diisi.');
    }

    public function test_existing_local_trainer_can_be_given_login_credentials(): void
    {
        $trainer = Trainer::create(['name' => 'Trainer Lama', 'opd_id' => $this->trainerOpd()]);
        $this->put(route('admin.trainer.update', $trainer), [
            'source' => 'local', 'name' => 'Trainer Lama', 'opd_id' => $this->trainerOpd(),
            'email' => 'lama@example.test',
            'password' => 'secret123', 'password_confirmation' => 'secret123',
        ])->assertRedirect(route('admin.trainer.index'));

        $this->assertSame($trainer->id, $trainer->fresh()->id);
        $this->assertSame('lama@example.test', $trainer->fresh()->user->email);
        $this->assertTrue($trainer->fresh()->user->hasRole('trainer'));
    }

    public function test_local_photo_upload_and_multipart_update(): void
    {
        $originalDirectory = User::$directoryImage;
        $directory = storage_path('framework/testing/trainer-'.uniqid().'/');
        User::$directoryImage = $directory;
        try {
            $this->post(route('admin.trainer.store'), [
                'source' => 'local', 'opd_id' => $this->trainerOpd(), 'name' => 'Foto lokal', 'photo' => UploadedFile::fake()->image('trainer.png'),
                'email' => 'foto@example.test', 'password' => 'secret123', 'password_confirmation' => 'secret123',
            ])->assertRedirect(route('admin.trainer.index'));
            $trainer = Trainer::where('name', 'Foto lokal')->firstOrFail();
            $oldPhoto = $trainer->user->image;
            $this->assertFileExists($directory.$oldPhoto);
            $this->post(route('admin.trainer.update', $trainer), [
                '_method' => 'PUT', 'source' => 'user', 'opd_id' => $this->trainerOpd(), 'name' => 'Foto baru',
                'email' => 'foto@example.test',
                'photo' => UploadedFile::fake()->image('updated.jpg'),
            ])->assertRedirect(route('admin.trainer.index'));
            $this->assertSame('Foto baru', $trainer->fresh()->name);
            $this->assertFileExists($directory.$trainer->fresh()->user->image);
            $this->assertFileDoesNotExist($directory.$oldPhoto);
        } finally {
            User::$directoryImage = $originalDirectory;
            File::deleteDirectory($directory);
        }
    }

    public function test_sso_profile_follows_user_and_rejects_manual_edits(): void
    {
        $user = $this->ssoUser();
        $user->assignRole('participant');
        $this->post(route('admin.trainer.store'), ['source' => 'user', 'user_id' => $user->id])
            ->assertRedirect(route('admin.trainer.index'));
        $trainer = Trainer::where('user_id', $user->id)->firstOrFail();
        $this->assertTrue($user->fresh()->hasAllRoles(['trainer', 'participant']));
        $user->update(['name' => 'Profil terbaru', 'image' => 'new.jpg']);
        UserMeta::create(['user_id' => $user->id, 'key' => 'nama_jabatan', 'value' => 'Widyaiswara']);
        $trainer = $trainer->fresh();
        $this->assertSame('Profil terbaru', $trainer->name);
        $this->assertSame('Widyaiswara', $trainer->jabatan);
        $this->assertSame($user->fresh()->profile_picture, $trainer->photo_src);
        $this->getJson(route('admin.trainer.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('data.0.name', 'Profil terbaru')
            ->assertJsonPath('data.0.jabatan', 'Widyaiswara')->assertJsonPath('data.0.source', 'user');
        $this->putJson(route('admin.trainer.update', $trainer), ['source' => 'local', 'opd_id' => $this->trainerOpd(), 'name' => 'Override', 'user_id' => $this->ssoUser()->id])
            ->assertUnprocessable()->assertJsonValidationErrors('source');
        $this->assertSame($user->id, $trainer->fresh()->user_id);
        $this->assertSame('Profil terbaru', $trainer->fresh()->name);
    }

    public function test_sso_requires_eligible_user_and_prevents_duplicates(): void
    {
        $valid = $this->ssoUser();
        $local = User::factory()->create(['auth_source' => 'local']);
        $inactive = $this->ssoUser(false);
        $deleted = $this->ssoUser();
        $deleted->delete();
        $this->postJson(route('admin.trainer.store'), ['source' => 'user', 'user_id' => $local->id])
            ->assertUnprocessable()->assertJsonValidationErrors('opd_id');
        foreach ([$inactive->id, $deleted->id, '123'] as $id) {
            $this->postJson(route('admin.trainer.store'), ['source' => 'user', 'user_id' => $id])
                ->assertUnprocessable()->assertJsonValidationErrors('user_id');
        }
        $this->postJson(route('admin.trainer.store'), ['source' => 'user', 'user_id' => $valid->id, 'name' => 'Manual'])
            ->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->post(route('admin.trainer.store'), ['source' => 'user', 'user_id' => $valid->id])->assertRedirect();
        $this->postJson(route('admin.trainer.store'), ['source' => 'user', 'user_id' => $valid->id])
            ->assertUnprocessable()->assertJsonValidationErrors('user_id');
        $this->assertSame(1, Trainer::where('user_id', $valid->id)->count());
    }

    public function test_local_source_and_user_cannot_be_changed(): void
    {
        $trainer = Trainer::create(['name' => 'Lokal']);
        $user = $this->ssoUser();
        $this->putJson(route('admin.trainer.update', $trainer), ['source' => 'user', 'user_id' => $user->id, 'name' => 'Lokal'])
            ->assertUnprocessable()->assertJsonValidationErrors(['source', 'user_id']);
        $this->assertNull($trainer->fresh()->user_id);
    }

    public function test_deleting_sso_revokes_only_trainer_role_and_readding_restores_same_record(): void
    {
        $user = $this->ssoUser();
        $user->assignRole('participant');
        $this->post(route('admin.trainer.store'), ['source' => 'user', 'user_id' => $user->id])->assertRedirect();
        $trainer = Trainer::where('user_id', $user->id)->firstOrFail();
        $this->delete(route('admin.trainer.destroy', $trainer))->assertRedirect(route('admin.trainer.index'));
        $this->assertSoftDeleted($trainer);
        $this->assertNotNull(User::find($user->id));
        $this->assertFalse($user->fresh()->hasRole('trainer'));
        $this->assertTrue($user->fresh()->hasRole('participant'));
        $this->post(route('admin.trainer.store'), ['source' => 'user', 'user_id' => $user->id])->assertRedirect();
        $this->assertSame($trainer->id, Trainer::where('user_id', $user->id)->firstOrFail()->id);
        $this->assertTrue($user->fresh()->hasRole('trainer'));
    }

    public function test_registration_candidates_include_active_opd_accounts_from_every_source_and_exclude_trainer_and_super_admin(): void
    {
        $opdId = $this->trainerOpd();
        $local = User::factory()->create(['auth_source' => 'local']);
        $localWithOpd = User::factory()->create(['auth_source' => 'local', 'opd_id' => $opdId]);
        $sso = $this->ssoUser();
        $prestige = User::factory()->create(['auth_source' => 'prestige', 'opd_id' => $opdId]);
        $alreadyTrainer = $this->ssoUser();
        $alreadyTrainer->assignRole('trainer');
        $alreadyLinked = $this->ssoUser();
        Trainer::create(['user_id' => $alreadyLinked->id, 'name' => 'Sudah terhubung']);
        $superAdmin = User::factory()->create(['auth_source' => 'local', 'opd_id' => $opdId]);
        $superAdmin->assignRole('super_admin');
        $withoutOpd = User::factory()->create(['auth_source' => 'sso']);
        $this->ssoUser(false);

        $profiles = app(TrainerService::class)->getEligibleUsers()->toArray();
        $this->assertCount(4, $profiles);
        $this->assertEqualsCanonicalizing([$local->id, $localWithOpd->id, $sso->id, $prestige->id], array_column($profiles, 'id'));
        $this->assertContains('GAPURA lokal', array_column($profiles, 'source_label'));
        $this->assertContains('Kedaton SSO', array_column($profiles, 'source_label'));
        $this->assertContains('Prestige', array_column($profiles, 'source_label'));
        $this->assertNotContains($withoutOpd->id, array_column($profiles, 'id'));
    }

    public function test_master_trainer_registration_adds_role_and_linked_profile_for_local_sso_and_prestige_users(): void
    {
        $opdId = $this->trainerOpd();
        $users = [
            User::factory()->create(['auth_source' => 'local']),
            User::factory()->create(['auth_source' => 'local', 'opd_id' => $opdId]),
            $this->ssoUser(),
            User::factory()->create(['auth_source' => 'prestige', 'opd_id' => $opdId]),
        ];

        foreach ($users as $user) {
            $payload = ['source' => 'user', 'user_id' => $user->id];
            if ($user->usesLocalAuthentication()) $payload['opd_id'] = $opdId;
            $this->post(route('admin.trainer.store'), $payload)
                ->assertRedirect(route('admin.trainer.index'));
            $this->assertTrue($user->fresh()->hasRole('trainer'));
            $this->assertSame((string) $user->fresh()->opd_id, (string) Trainer::where('user_id', $user->id)->sole()->organization_id);
        }

        $this->assertSame(4, Trainer::whereNotNull('user_id')->count());
        $this->postJson(route('admin.trainer.store'), ['source' => 'user', 'user_id' => $users[0]->id, 'opd_id' => $opdId])
            ->assertUnprocessable()->assertJsonValidationErrors('user_id');
        $this->assertSame(4, Trainer::whereNotNull('user_id')->count());
    }

    public function test_super_admin_can_edit_linked_user_profile_in_master_trainer(): void
    {
        $user = $this->ssoUser();
        $user->assignRole('trainer');
        $trainer = app(TrainerService::class)->getOrCreateForUser($user);
        $newOpd = Opd::create(['code' => 'EDIT-OPD', 'name' => 'OPD Baru', 'is_active' => true]);

        $this->get(route('admin.trainer.edit', $trainer))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Trainer/Form')->where('trainer.source', 'user'));

        $this->put(route('admin.trainer.update', $trainer), [
            'source' => 'user', 'name' => 'Nama Admin', 'jabatan' => 'Trainer Senior', 'opd_id' => $newOpd->id,
        ])->assertRedirect(route('admin.trainer.index'));

        $this->assertSame('Nama Admin', $user->fresh()->name);
        $this->assertSame((string) $newOpd->id, (string) $user->fresh()->opd_id);
        $this->assertSame('Trainer Senior', $user->metas()->where('key', 'nama_jabatan')->value('value'));
        $this->assertSame('sso', $user->fresh()->auth_source);
    }

    public function test_administrator_can_edit_a_user_linked_trainer(): void
    {
        $user = $this->ssoUser();
        $user->assignRole('trainer');
        $trainer = app(TrainerService::class)->getOrCreateForUser($user);
        $permission = Permission::findOrCreate('trainer.manage', 'web');
        $role = Role::findOrCreate('administrator', 'web');
        $role->givePermissionTo($permission);
        $admin = User::factory()->create(['auth_source' => 'local']);
        $admin->assignRole($role);

        $this->actingAs($admin)->get(route('admin.trainer.edit', $trainer))->assertOk();
        $this->put(route('admin.trainer.update', $trainer), [
            'source' => 'user', 'name' => 'Diedit admin', 'jabatan' => '', 'opd_id' => $this->trainerOpd(),
        ])->assertRedirect(route('admin.trainer.index'));
        $this->assertSame('Diedit admin', $user->fresh()->name);
    }
    public function test_users_with_trainer_role_appear_in_master_and_course_choices_without_duplicates(): void
    {
        $local = User::factory()->create(['auth_source' => 'local']);
        $sso = $this->ssoUser();
        $local->assignRole('trainer');
        $sso->assignRole(['participant', 'trainer']);
        $participant = User::factory()->create();
        $participant->assignRole('participant');
        $service = app(TrainerService::class);
        $this->assertCount(2, $service->getForAdmin());
        $this->assertCount(2, $service->getForForm());
        $this->assertSame(2, Trainer::count());
        $this->assertDatabaseMissing('trainers', ['user_id' => $participant->id]);
        $this->getJson(route('admin.trainer.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonFragment(['name' => $local->name, 'source_label' => 'Akun lokal'])
            ->assertJsonFragment(['name' => $sso->name, 'source_label' => 'Kedaton SSO']);
    }

    public function test_super_admin_can_edit_a_user_linked_trainer_from_the_master_list(): void
    {
        $user = $this->ssoUser();
        $user->assignRole('trainer');
        $trainer = app(TrainerService::class)->getOrCreateForUser($user);

        $this->get(route('admin.trainer.edit', $trainer))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Trainer/Form')->where('trainer.source', 'user'));
    }

    public function test_assigning_trainer_role_in_user_management_creates_master_profile(): void
    {
        $user = $this->ssoUser();
        $this->put(route('admin.management.user.update', $user), ['roles' => ['participant', 'trainer']])->assertRedirect();
        $trainer = Trainer::where('user_id', $user->id)->firstOrFail();
        $this->put(route('admin.management.user.update', $user), ['roles' => ['participant', 'trainer']])->assertRedirect();
        $this->assertSame(1, Trainer::where('user_id', $user->id)->count());
        $this->assertSame($trainer->id, Trainer::where('user_id', $user->id)->first()->id);
    }

    public function test_creating_a_user_with_trainer_role_creates_master_profile(): void
    {
        $this->post(route('admin.management.user.store'), [
            'name' => 'Trainer dari Pengguna', 'email' => 'trainer-role@example.test', 'auth_source' => 'local',
            'password' => 'password123', 'password_confirmation' => 'password123', 'roles' => ['trainer'],
        ])->assertRedirect(route('admin.management.user.index'));
        $user = User::where('email', 'trainer-role@example.test')->firstOrFail();
        $this->assertDatabaseHas('trainers', ['user_id' => $user->id]);
    }

    public function test_reassigned_role_restores_the_same_master_profile(): void
    {
        $user = $this->ssoUser();
        $trainer = app(TrainerService::class)->getOrCreateForUser($user);
        $trainer->delete();
        $user->assignRole('trainer');
        $this->assertSame($trainer->id, app(TrainerService::class)->getForAdmin()->sole()->id);
        $this->assertSame(1, Trainer::withTrashed()->where('user_id', $user->id)->count());
    }

    public function test_local_trainer_requires_an_active_opd(): void
    {
        $credentials = ['email' => 'trainer-opd@example.test', 'password' => 'secret123', 'password_confirmation' => 'secret123'];
        $this->postJson(route('admin.trainer.store'), ['source' => 'local', 'name' => 'Trainer tanpa OPD', ...$credentials])
            ->assertUnprocessable()->assertJsonValidationErrors('opd_ids');
        $opd = Opd::create(['code' => 'INACTIVE', 'name' => 'Nonaktif', 'is_active' => false]);
        $this->postJson(route('admin.trainer.store'), ['source' => 'local', 'name' => 'Trainer nonaktif', 'opd_id' => $opd->id, ...$credentials])
            ->assertUnprocessable()->assertJsonValidationErrors('opd_ids.0');
    }

    public function test_master_opd_filter_covers_local_and_user_linked_trainers(): void
    {
        $user = $this->ssoUser();
        $user->assignRole('trainer');
        $local = Trainer::create(['name' => 'Lokal Pendidikan', 'opd_id' => $this->trainerOpd()]);
        $response = $this->getJson(route('admin.trainer.index', ['opd_id' => $user->opd_id]), ['X-Requested-With' => 'XMLHttpRequest']);
        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonFragment(['opd_name' => $user->opd->name]);
        $this->getJson(route('admin.trainer.index', ['opd_id' => $local->opd_id]), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonFragment(['name' => 'Lokal Pendidikan', 'opd_name' => 'Dinas Pendidikan']);
    }

    public function test_user_linked_trainer_follows_current_user_opd(): void
    {
        $user = $this->ssoUser();
        $trainer = app(TrainerService::class)->getOrCreateForUser($user);
        $this->assertSame($user->opd_id, $trainer->organization_id);
        $user->update(['opd_id' => $this->trainerOpd()]);
        $this->assertSame($this->trainerOpd(), $trainer->fresh()->organization_id);
        $this->assertSame('Dinas Pendidikan', $trainer->fresh()->organization_name);
    }

    public function test_local_user_opd_can_be_assigned_from_user_management(): void
    {
        $user = User::factory()->create(['auth_source' => 'local']);
        $this->put(route('admin.management.user.update', $user), [
            'name' => $user->name, 'email' => $user->email, 'roles' => ['trainer'], 'opd_id' => $this->trainerOpd(),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $trainer = Trainer::where('user_id', $user->id)->firstOrFail();
        $this->assertSame($this->trainerOpd(), $trainer->organization_id);
    }

    public function test_course_requests_reject_other_opd_trainers_but_allow_same_opd_and_global_scope(): void
    {
        $user = $this->ssoUser();
        $linked = app(TrainerService::class)->getOrCreateForUser($user);
        $local = Trainer::create(['name' => 'Trainer Pendidikan', 'opd_id' => $this->trainerOpd()]);
        foreach ([\App\Http\Requests\Course\StoreCourseRequest::class, \App\Http\Requests\Course\UpdateCourseRequest::class] as $class) {
            foreach ([[$local->id, $this->trainerOpd(), true], [$linked->id, $this->trainerOpd(), false], [$linked->id, $user->opd_id, true], [$linked->id, null, true]] as [$trainerId, $opdId, $valid]) {
                $request = new $class();
                $payload = ['description' => 'Materi kelas', 'trainers' => [$trainerId], 'opd_id' => $opdId];
                $request->merge($payload);
                $rules = array_intersect_key($request->rules(), $payload + ['trainers.*' => null]);
                $validator = \Illuminate\Support\Facades\Validator::make($payload, $rules);
                $request->withValidator($validator);
                $this->assertSame($valid, $validator->passes());
                if (! $valid) $this->assertTrue($validator->errors()->has('trainers'));
            }
        }
    }

}
