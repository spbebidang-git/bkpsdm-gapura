<?php

namespace Tests\Feature;

use App\Models\Course\Activity\Assessment\Question;
use App\Models\Course\Course;
use App\Models\Course\Activity\Activity;
use App\Models\Course\Activity\Interactive;
use App\Models\Course\Activity\Literature;
use App\Models\Course\Activity\Task;
use App\Models\Identity\User;
use App\Models\Learning\Learn;
use App\Models\Learning\LearnItem;
use App\Services\Course\Activity\ActivityService;
use App\Services\Course\CourseService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Database\Seeders\ActivitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivitySeederTest extends TestCase
{
    use RefreshDatabase;

    private string $publicDirectory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->publicDirectory = storage_path('framework/testing/activity-seeder-'.Str::uuid());
        $this->app->usePublicPath($this->publicDirectory);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->publicDirectory);
        parent::tearDown();
    }

    public function test_every_demo_activity_can_supply_complete_learning_page_data(): void
    {
        $courses = collect(['open', 'scheduled'])->map(fn ($mode) => Course::factory()->create(['access_mode' => $mode]));
        $user = User::factory()->create();
        $this->seed(ActivitySeeder::class);

        foreach ($courses as $course) {
            app(CourseService::class)->generatePrePostTestVariant($user->id, $course);
            $activities = $course->activities()->orderBy('sort_order')->get();
            $this->assertCount($course->access_mode === 'open' ? 6 : 5, $activities);
            $this->assertSame(range(1, $activities->count()), $activities->pluck('sort_order')->map(fn ($order) => (int) $order)->all());
            $this->assertSame('post-test', $activities->last()->type);
            $this->assertSame(100, (int) $activities->sum('value'));
            foreach ($activities as $activity) {
                $this->assertNotEmpty($activity->instruction);
                $detail = app(ActivityService::class)->getDetailActivityParticipant($user->id, $activity->id);
                $this->assertSame($activity->id, $detail['activity_id']);
                $this->assertNotEmpty($detail['title']);
                $this->assertNotEmpty($detail['data']);
                if (in_array($activity->type, ['pre-test', 'post-test'])) {
                    $this->assertGreaterThan(0, $activity->timer);
                    $this->assertCount(5, $detail['data']['question_items']);
                    $this->assertSame(70, (int) $detail['data']['min_score']);
                } elseif ($activity->type === 'video') {
                    $this->assertNotEmpty($detail['data']['src_video']);
                    $this->assertGreaterThan(0, $detail['data']['duration']);
                } elseif ($activity->type === 'literature') {
                    $path = public_path($detail['data']['src_pdf']);
                    $this->assertFileExists($path);
                    $this->assertStringStartsWith('%PDF-', File::get($path));
                    $this->assertGreaterThan(0, $detail['data']['duration']);
                } elseif ($activity->type === 'task') {
                    $this->assertNotEmpty($detail['data']['description']);
                } elseif ($activity->type === 'meeting') {
                    $this->assertNotEmpty($detail['data']['meeting_url']);
                    $this->assertSame(1, (int) Interactive::where('activity_id', $activity->id)->value('end_meeting'));
                }
            }
        }
    }

    public function test_reseeding_repairs_missing_details_without_removing_progress_or_custom_activities(): void
    {
        $course = Course::factory()->open()->create();
        $user = User::factory()->create();
        $this->seed(ActivitySeeder::class);
        app(CourseService::class)->generatePrePostTestVariant($user->id, $course);
        $originalIds = $course->activities()->pluck('id')->all();
        $question = Question::first();
        $variantId = get_user_meta($user->id, $question->activity_id);
        $custom = Activity::create(['course_id' => $course->id, 'type' => 'task', 'title' => 'Kegiatan Tambahan', 'sort_order' => 20]);
        $video = $course->activities()->where('type', 'video')->first();
        $learn = Learn::create(['course_id' => $course->id, 'participant_id' => $user->id]);
        $item = LearnItem::create(['learn_id' => $learn->id, 'activity_id' => $video->id, 'type' => 'video', 'title' => $video->title, 'status' => 'done']);
        Interactive::where('activity_id', $video->id)->update(['url' => '', 'description' => '', 'duration' => null]);
        $video->update(['instruction' => null, 'sort_order' => 0]);
        Literature::query()->forceDelete();
        Task::query()->forceDelete();

        $this->seed(ActivitySeeder::class);

        $this->assertSame($originalIds, $course->activities()->where('id', '!=', $custom->id)->pluck('id')->all());
        $this->assertNotNull($custom->fresh());
        $this->assertSame('done', $item->fresh()->status);
        $this->assertSame($variantId, get_user_meta($user->id, $question->activity_id));
        $this->assertNotEmpty($video->fresh()->instruction);
        $this->assertNotEmpty(Interactive::where('activity_id', $video->id)->first()->url);
        $this->assertSame(1, Literature::count());
        $this->assertSame(1, Task::count());
    }

    public function test_demo_tests_have_complete_variants_with_b_as_the_only_correct_answer(): void
    {
        foreach (['open', 'scheduled'] as $mode) {
            Course::factory()->create(['access_mode' => $mode]);
        }

        // A repeated seed must still produce the same number of test packages.
        for ($run = 0; $run < 2; $run++) {
            $this->seed(ActivitySeeder::class);
            $questions = Question::with('questionVariant.questionItems.questionOptions')->get();
            $this->assertCount(4, $questions);
            $this->assertSame(2, $questions->where('type', 'pre')->count());
            $this->assertSame(2, $questions->where('type', 'post')->count());

            foreach ($questions as $question) {
                $this->assertCount(2, $question->questionVariant);
                foreach ($question->questionVariant as $variant) {
                    $this->assertFalse((bool) $variant->mix);
                    $this->assertCount(5, $variant->questionItems);
                    $this->assertSame(100, $variant->questionItems->sum('score'));
                    foreach ($variant->questionItems as $item) {
                        $this->assertCount(4, $item->questionOptions);
                        $this->assertSame([1, 2, 3, 4], $item->questionOptions->pluck('sort_order')->map(fn ($order) => (int) $order)->all());
                        $correct = $item->questionOptions->filter(fn ($option) => (bool) $option->is_correct);
                        $this->assertCount(1, $correct);
                        $this->assertSame(2, (int) $correct->first()->sort_order);
                    }
                }
            }
        }
    }
}
