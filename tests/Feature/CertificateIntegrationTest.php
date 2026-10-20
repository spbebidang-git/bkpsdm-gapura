<?php

namespace Tests\Feature;

use App\Logging\Redactor;
use App\Models\Course\Category;
use App\Models\Course\Course;
use App\Models\Identity\User;
use App\Models\Identity\UserMeta;
use App\Models\Learning\Learn;
use App\Models\Learning\ParticipantNotification;
use App\Services\Auth\TokenService;
use App\Services\Integration\CertificateSyncService;
use App\Services\Learning\LearnLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CertificateIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.sso.base_url' => 'https://prestige.test']);
        Http::preventStrayRequests();
        $this->mock(TokenService::class, function ($mock) {
            $mock->shouldReceive('getToken')->andReturn('Bearer original');
            $mock->shouldReceive('forceRefresh')->andReturn('Bearer refreshed');
        });
    }

    private function fake(array $responses): void
    {
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake($responses);
    }

    private function learn(?Course $course = null, string $nip = '199210212019031005'): Learn
    {
        $user = User::factory()->create(['nip' => null]);
        UserMeta::create(['user_id' => $user->id, 'key' => 'nip', 'value' => $nip]);
        $course ??= Course::factory()->open()->create(['category_id' => Category::firstOrCreate(['code' => '5'], ['title' => 'Pelatihan'])->id]);

        return Learn::create(['participant_id' => $user->id, 'course_id' => $course->id, 'finished_at' => '2026-10-10', 'is_post_certificate' => false]);
    }

    private function ready(Learn $learn, string $code = '2659'): void
    {
        $learn->course->update(['prestige_certificate_code' => $code]);
        $learn->update(['is_post_certificate' => true, 'prestige_certificate_registered_at' => now()]);
    }

    private function row(Learn $learn, string $url = 'https://example.test/cert.pdf'): array
    {
        return ['nama_kegiatan' => $learn->course->title, 'kode_kegiatan' => '5   ', 'status' => '2',
            'peserta' => [['nip' => get_user_meta($learn->participant_id, 'nip'), 'sertifikat' => $url]]];
    }

    private function created(array $missing = []): array
    {
        return ['message' => 'Berhasil Disimpan', 'kode' => 2659, 'nip_tidak_ditemukan' => $missing];
    }

    public function test_column_nip_without_meta_supports_both_push_and_pull(): void
    {
        $learn = $this->learn();
        $nip = '199004102022032002';
        $learn->participant->update(['nip' => $nip]);
        UserMeta::where('user_id', $learn->participant_id)->where('key', 'nip')->delete();
        $this->fake(['*' => Http::sequence()->push($this->created())->push(['message' => 'Berhasil Disimpan'])]);
        $this->artisan('app:post-certificate-command')->assertSuccessful();
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/post_sertifikat') && $r['peserta'] === $nip);
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/post_sertifikat_pegawai') && $r['nip'] === $nip);
        $row = $this->row($learn);
        $row['peserta'][0]['nip'] = $nip;
        $this->fake(['*' => Http::response([$row])]);
        $this->assertSame(1, app(CertificateSyncService::class)->syncPull()['updated']);
        $this->assertSame('https://example.test/cert.pdf', $learn->fresh()->url_certificate);
    }

    public function test_push_creates_then_registers_employee_with_returned_code_and_refreshes(): void
    {
        Log::spy();
        $learn = $this->learn();
        $this->fake(['*' => Http::sequence()->push([], 401)->push($this->created())->push(['message' => 'Berhasil Disimpan'])]);
        $this->artisan('app:post-certificate-command')->assertSuccessful();
        Http::assertSentCount(3);
        Http::assertSent(fn ($r) => $r->url() === 'https://prestige.test/api/post_sertifikat'
            && $r->method() === 'POST' && $r->hasHeader('Authorization', 'Bearer refreshed')
            && $r->hasHeader('Content-Type', 'application/x-www-form-urlencoded')
            && $r['kode_kegiatan'] === '5' && $r['peserta'] === '199210212019031005'
            && $r['tgl_mulai'] === '2026-10-10' && $r['tgl_selesai'] === '2026-10-10');
        Http::assertSent(fn ($r) => $r->url() === 'https://prestige.test/api/post_sertifikat_pegawai'
            && $r->hasHeader('Authorization', 'Bearer refreshed') && $r['kode'] === '2659' && $r['nip'] === '199210212019031005');
        Log::shouldHaveReceived('info')->with('Respons layanan sertifikat diterima.',
            \Mockery::on(fn ($c) => str_ends_with($c['endpoint'], '/post_sertifikat')
                && $c['http_status'] === 200 && ! isset($c['data'], $c['parameters']) && $c['attempt'] === 2))->once();
        Log::shouldHaveReceived('info')->with('Respons layanan sertifikat diterima.',
            \Mockery::on(fn ($c) => str_ends_with($c['endpoint'], '/post_sertifikat_pegawai')
                && $c['http_status'] === 200 && ! isset($c['parameters'], $c['raw_content'])))->once();
        $this->assertSame('2659', $learn->course->fresh()->prestige_certificate_code);
        $this->assertNotNull($learn->fresh()->prestige_certificate_registered_at);
        $this->assertEquals(1, $learn->fresh()->is_post_certificate);
    }

    public function test_failed_registration_keeps_code_and_retries_only_employee_then_adds_late_participant(): void
    {
        $learn = $this->learn();
        $this->fake(['*' => Http::sequence()->push($this->created())->push(['message' => 'Gagal'], 500)]);
        $this->artisan('app:post-certificate-command')->assertFailed();
        $this->assertSame('2659', $learn->course->fresh()->prestige_certificate_code);
        $this->assertNull($learn->fresh()->prestige_certificate_registered_at);
        $this->assertEquals(0, $learn->fresh()->is_post_certificate);
        $this->fake(['https://prestige.test/api/post_sertifikat_pegawai' => Http::response(['message' => 'Berhasil Disimpan'])]);
        $this->artisan('app:post-certificate-command')->assertSuccessful();
        Http::assertSentCount(1);
        $late = $this->learn($learn->course->fresh(), '12345');
        $this->fake(['https://prestige.test/api/post_sertifikat_pegawai' => Http::response(['message' => 'Berhasil Disimpan'])]);
        $this->artisan('app:post-certificate-command')->assertSuccessful();
        Http::assertSentCount(1);
        Http::assertSent(fn ($r) => $r['kode'] === '2659' && $r['nip'] === '12345');
        $this->assertNotNull($late->fresh()->prestige_certificate_registered_at);
    }

    public function test_multiple_participants_share_one_activity_and_each_is_registered(): void
    {
        $first = $this->learn();
        $second = $this->learn($first->course, '12345');
        $this->fake(['*' => Http::sequence()->push($this->created())->push(['message' => 'Berhasil Disimpan'])->push(['message' => 'Berhasil Disimpan'])]);
        $this->artisan('app:post-certificate-command')->assertSuccessful();
        Http::assertSentCount(3);
        $this->assertNotNull($first->fresh()->prestige_certificate_registered_at);
        $this->assertNotNull($second->fresh()->prestige_certificate_registered_at);
    }

    public function test_partial_push_does_not_register_missing_nip(): void
    {
        $accepted = $this->learn();
        $missing = $this->learn($accepted->course, '12345');
        $this->fake(['*' => Http::sequence()->push($this->created(['12345']))->push(['message' => 'Berhasil Disimpan'])]);
        $this->artisan('app:post-certificate-command')->assertFailed();
        Http::assertSentCount(2);
        $this->assertNotNull($accepted->fresh()->prestige_certificate_registered_at);
        $this->assertNull($missing->fresh()->prestige_certificate_registered_at);
    }

    public function test_failed_creation_never_calls_employee_endpoint(): void
    {
        foreach ([[['success' => false], 200], ['not json', 200], [['error' => 'failed'], 500]] as [$body, $status]) {
            $learn = $this->learn();
            $this->fake(['*' => Http::response($body, $status)]);
            $this->artisan('app:post-certificate-command', ['--course' => $learn->course_id])->assertFailed();
            Http::assertSentCount(1);
            $this->assertNull($learn->course->fresh()->prestige_certificate_code);
            $this->assertNull($learn->fresh()->prestige_certificate_registered_at);
        }
    }

    public function test_pull_requires_code_and_completed_employee_registration(): void
    {
        $learn = $this->learn();
        $this->assertSame(0, app(CertificateSyncService::class)->syncPull()['updated']);
        $learn->course->update(['prestige_certificate_code' => '2659']);
        $this->assertSame(0, app(CertificateSyncService::class)->syncPull()['updated']);
        Http::assertNothingSent();
    }

    public function test_legacy_sent_records_without_code_are_not_recreated(): void
    {
        $learn = $this->learn();
        $learn->update(['is_post_certificate' => true]);
        $this->artisan('app:post-certificate-command')->assertFailed();
        Http::assertNothingSent();
        $learn->course->update(['prestige_certificate_code' => '2659']);
        $this->fake(['https://prestige.test/api/post_sertifikat_pegawai' => Http::response(['message' => 'Berhasil Disimpan'])]);
        $this->artisan('app:post-certificate-command')->assertSuccessful();
        Http::assertSentCount(1);
    }

    public function test_pull_uses_stored_code_refreshes_and_replaces_url_without_duplicate_notice(): void
    {
        $learn = $this->learn();
        $this->ready($learn);
        $this->fake(['*' => Http::sequence()->push([], 401)->push([$this->row($learn)])]);
        $this->assertSame(1, app(CertificateSyncService::class)->syncPull()['updated']);
        Http::assertSent(fn ($r) => $r->url() === 'https://prestige.test/api/get_data_sertifikat'
            && $r->method() === 'POST' && $r->hasHeader('Authorization', 'Bearer refreshed')
            && str_contains($r->header('Content-Type')[0] ?? '', 'multipart/form-data')
            && preg_match('/name="kode"[^\r\n]*\r\n(?:[^\r\n]+\r\n)*\r\n2659\r\n/', $r->body()));
        $this->fake(['*' => Http::response([$this->row($learn, 'https://example.test/new.pdf')])]);
        $this->assertSame(1, app(CertificateSyncService::class)->syncPull()['updated']);
        $this->assertSame(0, app(CertificateSyncService::class)->syncPull()['updated']);
        $this->assertSame('https://example.test/new.pdf', $learn->fresh()->url_certificate);
        $this->assertSame(1, ParticipantNotification::where('type', 'certificate')->count());
        foreach ([null, '', '-', 'http-not-a-url', 'javascript:alert(1)'] as $url) {
            $this->assertFalse(app(LearnLifecycleService::class)->setCertificate($learn, $url));
        }
    }

    public function test_same_title_and_nip_are_scoped_to_requested_code(): void
    {
        $first = $this->learn();
        $second = $this->learn(Course::factory()->open()->create(['title' => $first->course->title]));
        $this->ready($first, '2659');
        $this->ready($second, '2660');
        $other = $this->learn($first->course, '12345');
        $other->update(['is_post_certificate' => true, 'prestige_certificate_registered_at' => now()]);
        $this->fake(['*' => Http::response([$this->row($first)])]);
        $this->assertSame(1, app(CertificateSyncService::class)->syncPull($first->course_id)['updated']);
        Http::assertSentCount(1);
        $this->assertNull($second->fresh()->url_certificate);
        $this->assertNull($other->fresh()->url_certificate);
    }

    public function test_pending_and_failed_responses_are_logged_with_code(): void
    {
        $learn = $this->learn();
        $this->ready($learn);
        $row = $this->row($learn, '-');
        Log::spy();
        $this->fake(['*' => Http::response([$row])]);
        $this->assertSame(0, app(CertificateSyncService::class)->syncPull()['updated']);
        Log::shouldHaveReceived('info')->with('Respons layanan sertifikat diterima.',
            \Mockery::on(fn ($c) => $c['http_status'] === 200 && ! isset($c['data']) && (string) $c['kode'] === '2659'))->once();
        $this->fake(['*' => Http::response('upstream error', 500)]);
        $this->artisan('app:set-url-certificate-command')->assertFailed();
        Log::shouldHaveReceived('error')->with('Respons layanan sertifikat diterima.',
            \Mockery::on(fn ($c) => $c['http_status'] === 500 && ! isset($c['raw_content'], $c['data'])))->once();
    }

    public function test_scheduler_fills_missing_urls_per_code_and_keeps_conflicting_urls_unchanged(): void
    {
        $rows = [];
        $course = null;
        foreach ([null, '', '-'] as $i => $url) {
            $learn = $this->learn($course, '12345'.$i);
            $course = $learn->course;
            $this->ready($learn);
            $learn->update(['url_certificate' => $url]);
            $rows[] = $this->row($learn);
        }
        $this->fake(['*' => Http::response($rows)]);
        $this->artisan('app:set-url-certificate-command')->assertSuccessful();
        Http::assertSentCount(1);
        $this->assertSame(3, Learn::where('url_certificate', 'https://example.test/cert.pdf')->count());
        $conflict = $rows[0];
        $conflict['peserta'][0]['sertifikat'] = 'https://example.test/conflict.pdf';
        $this->fake(['*' => Http::response([$rows[0], $conflict])]);
        $this->assertSame(0, app(CertificateSyncService::class)->syncPull()['updated']);
    }
}
