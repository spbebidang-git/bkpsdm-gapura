<?php

namespace Tests\Feature;

use App\Exports\CertificatesExport;
use App\Models\Course\Course;
use App\Models\Identity\User;
use App\Models\Learning\Learn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CertificateListTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_and_excel_use_the_same_availability_search_and_date_filters(): void
    {
        config(['app.dev_admin_bypass' => false]);
        Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $participant = User::factory()->create(['name' => 'Peserta Sertifikat']);
        $course = Course::factory()->create();
        foreach ([null, '', '-', 'https://example.test/certificate.pdf'] as $url) {
            Learn::create(['participant_id' => $participant->id, 'course_id' => $course->id, 'finished_at' => '2026-10-10', 'url_certificate' => $url]);
        }
        Learn::create(['participant_id' => $participant->id, 'course_id' => $course->id]);
        $filters = ['status_filter' => 'tidak_tersedia', 'course_filter' => $course->id, 'q' => 'Peserta Sertifikat', 'date_from' => '2026-10-01', 'date_to' => '2026-10-15'];
        $this->actingAs($admin)->get('/admin/certificate?'.http_build_query($filters), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('recordsFiltered', 3)->assertJsonCount(3, 'data');
        $this->get('/admin/certificate/export?'.http_build_query($filters))->assertOk()->assertDownload();
        Excel::fake();
        $this->get('/admin/certificate/export?'.http_build_query($filters))->assertOk();
        Excel::assertDownloaded('data-sertifikat-'.now()->format('Y-m-d-His').'.xlsx', fn (CertificatesExport $export) => $export->query()->count() === 3);
        $this->get('/admin/certificate?'.http_build_query(['status_filter' => 'tersedia']), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('recordsFiltered', 1);
    }

    public function test_guests_cannot_export_certificates(): void
    {
        config(['app.dev_admin_bypass' => false]);
        $this->get('/admin/certificate/export')->assertRedirect(route('sign-in.index'));
    }
}
