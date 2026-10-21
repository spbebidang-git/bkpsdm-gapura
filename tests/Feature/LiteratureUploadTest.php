<?php

namespace Tests\Feature;

use App\Helper\UploadMedia;
use App\Models\Course\Activity\Activity;
use App\Models\Course\Activity\Literature;
use App\Models\Course\Course;
use App\Models\Identity\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LiteratureUploadTest extends TestCase
{
    use RefreshDatabase;

    private string $publicDirectory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->publicDirectory = storage_path('framework/testing/literature-'.Str::uuid());
        $this->app->usePublicPath($this->publicDirectory);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->publicDirectory);
        parent::tearDown();
    }

    public function test_admin_can_create_literature_when_upload_directory_does_not_exist(): void
    {
        Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $course = Course::factory()->open()->create();

        $response = $this->actingAs($admin)->post(route('admin.course.activity.literature.store', $course->slug), [
            'title' => 'Modul PDF',
            'sub_title' => 'Pengantar',
            'value' => 10,
            'duration' => 1,
            'instruction' => 'Baca materi.',
            'content' => UploadedFile::fake()->createWithContent('modul.pdf', "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF"),
        ]);

        $response->assertRedirect(route('admin.course.activities.index', $course->slug))
            ->assertSessionHas('success', 'Literatur berhasil ditambahkan.');
        $activity = Activity::query()->where('course_id', $course->id)->where('type', 'literature')->firstOrFail();
        $literature = Literature::query()->where('activity_id', $activity->id)->firstOrFail();
        $this->assertFileExists(public_path(Literature::$directoryDocument.$literature->content));
    }

    public function test_shared_upload_helper_creates_a_missing_public_directory(): void
    {
        $filename = UploadMedia::file(
            UploadedFile::fake()->createWithContent('modul.pdf', "%PDF-1.4\n%%EOF"),
            'assets/media/documents/new-folder/'
        );

        $this->assertFileExists(public_path('assets/media/documents/new-folder/'.$filename));
    }
}
