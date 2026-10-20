<?php

namespace Tests\Feature;

use App\Http\Middleware\AdminLocale;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\IsAdminMiddleware;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\PresenceVerifierInterface;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class AdminValidationLocaleTest extends TestCase
{
    public function test_catalog_covers_every_installed_laravel_validation_message(): void
    {
        $english = Arr::dot(require base_path('vendor/laravel/framework/src/Illuminate/Translation/lang/en/validation.php'));
        $indonesian = Arr::dot(require base_path('lang/id/validation.php'));
        foreach ($english as $key => $value) {
            if (in_array($key, ['custom', 'attributes'], true) || str_starts_with($key, 'custom.') || str_starts_with($key, 'attributes.')) {
                continue;
            }
            $this->assertArrayHasKey($key, $indonesian);
            $this->assertNotSame($value, $indonesian[$key]);
            preg_match_all('/:[a-z_]+/', $value, $source);
            preg_match_all('/:[a-z_]+/', $indonesian[$key], $translated);
            $this->assertEqualsCanonicalizing($source[0], $translated[0], $key);
        }
    }

    public function test_admin_employee_status_endpoint_returns_indonesian_field_errors_and_summary(): void
    {
        $this->withoutMiddleware([IsAdminMiddleware::class, HandleInertiaRequests::class]);
        app()->setLocale('en');

        $this->postJson(route('admin.employee-status.store'), [])
            ->assertUnprocessable()
            ->assertJsonPath('errors.title.0', 'Nama status pegawai wajib diisi.')
            ->assertJsonPath('message', 'Nama status pegawai wajib diisi.');

        $this->assertSame('en', app()->getLocale());
        $this->assertSame('The Alamat email field must be a valid email address.', Validator::make(
            ['email' => 'invalid'], ['email' => 'email'], [], ['email' => 'Alamat email'],
        )->errors()->first('email'));
    }

    public function test_admin_notification_template_errors_hide_technical_nested_keys(): void
    {
        $this->withoutMiddleware([IsAdminMiddleware::class, HandleInertiaRequests::class]);
        $eventKey = array_key_first(config('notification_messages'));
        $response = $this->putJson(route('admin.management.notification-messages.update'), [
            'templates' => [$eventKey => ['title' => '', 'message' => '']],
        ])->assertUnprocessable();
        $errors = $response->json('errors');
        $this->assertSame(['Judul pesan notifikasi wajib diisi.'], $errors["templates.{$eventKey}.title"]);
        $this->assertSame(['Isi pesan notifikasi wajib diisi.'], $errors["templates.{$eventKey}.message"]);
    }

    public function test_validator_uses_indonesian_labels_and_type_specific_limits(): void
    {
        app()->setLocale('id');
        $validator = Validator::make([
            'email' => 'invalid', 'name' => 'panjang', 'quota' => 0, 'date' => 'invalid',
            'options' => ['option1' => ''], 'roles' => [], 'photo' => UploadedFile::fake()->create('photo.txt', 2049, 'text/plain'),
        ], [
            'email' => 'email', 'name' => 'string|max:3', 'quota' => 'integer|min:1', 'date' => 'date',
            'options.option1' => 'required', 'roles' => 'array|min:1', 'photo' => 'file|max:2048|mimes:png,jpg',
        ], [], ['name' => 'Nama pengguna']);
        $errors = $validator->errors();
        $this->assertSame('Alamat email harus berupa alamat email yang valid.', $errors->first('email'));
        $this->assertSame('Nama pengguna tidak boleh lebih dari 3 karakter.', $errors->first('name'));
        $this->assertSame('Kuota peserta minimal 1.', $errors->first('quota'));
        $this->assertSame('Tanggal harus berupa tanggal yang valid.', $errors->first('date'));
        $this->assertSame('Pilihan jawaban 1 wajib diisi.', $errors->first('options.option1'));
        $this->assertSame('Peran harus berisi minimal 1 item.', $errors->first('roles'));
        $this->assertContains('Ukuran Foto tidak boleh lebih dari 2048 KB.', $errors->get('photo'));
        $this->assertContains('Foto harus berupa berkas berformat: png, jpg.', $errors->get('photo'));
    }

    public function test_unique_and_exists_messages_are_translated_without_a_database(): void
    {
        app()->setLocale('id');
        $presence = Mockery::mock(PresenceVerifierInterface::class);
        $presence->shouldReceive('getCount')->with('opds', 'code', 'OPD-1', null, null, [])->andReturn(1);
        $presence->shouldReceive('getCount')->with('opds', 'id', 999, null, null, [])->andReturn(0);
        $validator = Validator::make(['code' => 'OPD-1', 'opd_id' => 999], ['code' => 'unique:opds,code', 'opd_id' => 'exists:opds,id']);
        $validator->setPresenceVerifier($presence);
        $this->assertSame('Kode OPD sudah digunakan.', $validator->errors()->first('code'));
        $this->assertSame('OPD yang dipilih tidak valid.', $validator->errors()->first('opd_id'));
    }

    public function test_locale_is_restored_when_validation_throws(): void
    {
        app()->setLocale('en');
        try {
            (new AdminLocale())->handle(Request::create('/admin/test'), function () {
                Validator::make([], ['email' => 'required'])->validate();
            });
            $this->fail('Expected a validation exception.');
        } catch (ValidationException $exception) {
            $this->assertSame(['Alamat email wajib diisi.'], $exception->errors()['email']);
        }
        $this->assertSame('en', app()->getLocale());
    }
}
