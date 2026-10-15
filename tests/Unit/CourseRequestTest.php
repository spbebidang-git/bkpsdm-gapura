<?php

namespace Tests\Unit;

use App\Http\Requests\Course\StoreCourseRequest;
use App\Http\Requests\Course\UpdateCourseRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CourseRequestTest extends TestCase
{
    public function test_course_create_and_edit_requests_no_longer_require_schedule_payloads(): void
    {
        $this->assertArrayNotHasKey('schedules', (new StoreCourseRequest())->rules());
        $this->assertArrayNotHasKey('schedules', (new UpdateCourseRequest())->rules());
    }

    public function test_thumbnail_is_required_only_when_storing_a_course(): void
    {
        $this->assertContains('required', (new StoreCourseRequest())->rules()['thumbnail']);
        $this->assertContains('nullable', (new UpdateCourseRequest())->rules()['thumbnail']);
    }

    public function test_validation_messages_are_presented_in_indonesian(): void
    {
        $request = new StoreCourseRequest();
        $validator = Validator::make(
            ['title' => ''],
            ['title' => $request->rules()['title']],
            $request->messages(),
            $request->attributes(),
        );

        $this->assertSame('Judul kelas wajib diisi.', $validator->errors()->first('title'));
    }

    public function test_empty_quill_content_is_rejected_as_an_empty_description(): void
    {
        $request = new StoreCourseRequest();
        $request->merge(['description' => '<p><br></p>']);
        $validator = Validator::make(
            ['description' => '<p><br></p>'],
            ['description' => $request->rules()['description']],
            $request->messages(),
            $request->attributes(),
        );
        $request->withValidator($validator);

        $this->assertSame('Deskripsi lengkap wajib diisi.', $validator->errors()->first('description'));
    }

    public function test_numeric_limits_previously_enforced_by_browser_are_validated_on_server(): void
    {
        $request = new StoreCourseRequest();
        $rules = $request->rules();
        $validator = Validator::make(
            ['type' => 'unschedule', 'jam_pelajaran' => '-1', 'expired' => '-1'],
            ['jam_pelajaran' => $rules['jam_pelajaran'], 'expired' => $rules['expired']],
            $request->messages(),
            $request->attributes(),
        );
        $this->assertSame('Jam pelajaran minimal 0.', $validator->errors()->first('jam_pelajaran'));
        $this->assertSame('Masa kedaluwarsa minimal 0.', $validator->errors()->first('expired'));

        $this->assertTrue(Validator::make(
            ['type' => 'schedule', 'jam_pelajaran' => '0', 'expired' => null],
            ['jam_pelajaran' => $rules['jam_pelajaran'], 'expired' => $rules['expired']],
        )->passes());
    }
}
