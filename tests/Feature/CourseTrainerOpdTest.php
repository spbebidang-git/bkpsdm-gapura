<?php

namespace Tests\Feature;

use App\Http\Middleware\IsAdminMiddleware;
use App\Models\Course\Course;
use App\Models\Course\Trainer;
use App\Models\Identity\EmployeeStatus;
use App\Models\Identity\Opd;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseTrainerOpdTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(IsAdminMiddleware::class);
    }

    public function test_course_opd_follows_selected_trainer_and_rejects_mixed_opds(): void
    {
        $firstOpd = Opd::create(['code' => '001', 'name' => 'Dinas Pendidikan', 'is_active' => true]);
        $secondOpd = Opd::create(['code' => '002', 'name' => 'Dinas Kesehatan', 'is_active' => true]);
        $firstTrainer = Trainer::create(['name' => 'Trainer Pendidikan', 'opd_id' => $firstOpd->id]);
        $secondTrainer = Trainer::create(['name' => 'Trainer Kesehatan', 'opd_id' => $secondOpd->id]);
        $status = EmployeeStatus::create(['title' => 'PNS']);
        $course = Course::factory()->open()->draft()->create();
        $payload = [
            'type' => 'unschedule',
            'category' => $course->category_id,
            'employee_status' => [$status->id],
            'title' => $course->title,
            'short_description' => $course->short_description,
            'description' => $course->description,
            'teaser_video' => '',
            'expired' => 72,
            'jam_pelajaran' => 8,
            'status' => 'draft',
            'trainers' => [$firstTrainer->id],
            'opd_id' => $secondOpd->id,
        ];

        $this->put(route('admin.course.update', $course->slug), $payload)
            ->assertRedirect(route('admin.course.index'));
        $this->assertSame($firstOpd->id, $course->fresh()->opd_id);
        $this->assertTrue($course->fresh()->trainers->contains($firstTrainer));

        $this->from(route('admin.course.edit', $course->slug))
            ->put(route('admin.course.update', $course->slug), [
                ...$payload,
                'trainers' => [$firstTrainer->id, $secondTrainer->id],
            ])->assertSessionHasErrors('trainers');
        $this->assertSame($firstOpd->id, $course->fresh()->opd_id);

        $this->put(route('admin.course.update', $course->slug), [
            ...$payload,
            'trainers' => [$secondTrainer->id],
        ])->assertRedirect(route('admin.course.index'));
        $this->assertSame($secondOpd->id, $course->fresh()->opd_id);

        $this->from(route('admin.course.edit', $course->slug))
            ->put(route('admin.course.update', $course->slug), [
                ...$payload,
                'trainers' => [],
            ])->assertSessionHasErrors('trainers');
    }
}
