<?php

namespace Tests\Feature;

use App\Models\Course\Category;
use App\Models\Course\Course;
use App\Models\Identity\User;
use App\Repositories\Course\Activity\Assessment\Contracts\QuestionItemRepositoryInterface;
use App\Repositories\Course\Activity\Assessment\Contracts\QuestionOptionRepositoryInterface;
use App\Repositories\Course\Activity\Assessment\Contracts\QuestionRepositoryInterface;
use App\Repositories\Course\Activity\Assessment\Contracts\QuestionVariantRepositoryInterface;
use App\Repositories\Course\Activity\Contracts\ActivityRepositoryInterface;
use App\Repositories\Course\Contracts\CourseRepositoryInterface;
use App\Repositories\Identity\Contracts\UserMetaRepositoryInterface;
use App\Repositories\Identity\Contracts\UserRepositoryInterface;
use App\Repositories\Learning\Contracts\EnrollmentRepositoryInterface;
use App\Repositories\Learning\Contracts\LearnItemRepositoryInterface;
use App\Repositories\Learning\Contracts\LearnRepositoryInterface;
use App\Repositories\Support\Contracts\HelpRepositoryInterface;
use App\Repositories\System\Contracts\SettingRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_base_crud_works_across_repository_domains(): void
    {
        $userRepository = $this->app->make(UserRepositoryInterface::class);
        $helpRepository = $this->app->make(HelpRepositoryInterface::class);
        $settingRepository = $this->app->make(SettingRepositoryInterface::class);

        $user = $userRepository->create([
            'name' => 'Repository User',
            'nip' => '1987654321',
            'email' => 'repository@example.test',
        ]);
        $userRepository->update($user, ['name' => 'Updated Repository User']);

        $help = $helpRepository->create([
            'question' => 'Repository?',
            'answer' => 'Repository answer.',
            'is_active' => true,
        ]);
        $setting = $settingRepository->create(['key' => 'repository-key', 'value' => 'enabled']);

        $this->assertSame('Updated Repository User', $userRepository->findOrFail($user->getKey())->name);
        $this->assertTrue($helpRepository->getActive()->contains($help));
        $this->assertTrue($helpRepository->delete($help));
        $this->assertNull($helpRepository->find($help->getKey()));
        $this->assertTrue($setting->is($settingRepository->findByKey('repository-key')));
    }

    public function test_course_and_assessment_queries_return_domain_models(): void
    {
        $category = Category::factory()->create();
        $published = Course::factory()->open()->published()->create([
            'category_id' => $category->id,
            'slug' => 'repository-published-course',
            'visibility' => 'public',
        ]);
        Course::factory()->draft()->create([
            'category_id' => $category->id,
            'slug' => 'repository-draft-course',
        ]);

        $courseRepository = $this->app->make(CourseRepositoryInterface::class);
        $activityRepository = $this->app->make(ActivityRepositoryInterface::class);
        $questionRepository = $this->app->make(QuestionRepositoryInterface::class);
        $variantRepository = $this->app->make(QuestionVariantRepositoryInterface::class);
        $itemRepository = $this->app->make(QuestionItemRepositoryInterface::class);
        $optionRepository = $this->app->make(QuestionOptionRepositoryInterface::class);

        $activity = $activityRepository->create([
            'course_id' => $published->id,
            'type' => 'pre-test',
            'title' => 'Pre Test',
            'value' => 0,
        ]);
        $question = $questionRepository->create([
            'activity_id' => $activity->id,
            'description' => 'Pre-test repository',
            'type' => 'pre',
            'min_score' => 70,
            'is_loop' => 0,
        ]);
        $variant = $variantRepository->create([
            'question_id' => $question->id,
            'code' => 'A',
            'variant' => 1,
            'mix' => false,
        ]);
        $item = $itemRepository->create([
            'question_variant_id' => $variant->id,
            'description' => 'Question item',
            'score' => 100,
        ]);
        $option = $optionRepository->create([
            'question_item_id' => $item->id,
            'option' => 'Correct answer',
            'is_correct' => true,
        ]);

        $this->assertTrue($published->is($courseRepository->findBySlug('repository-published-course')));
        $this->assertTrue($published->is($courseRepository->findPublishedBySlug('repository-published-course')));
        $this->assertCount(1, $courseRepository->getPublishedAvailable());
        $this->assertTrue($question->is($questionRepository->findByActivity($activity->id)));
        $this->assertTrue($variant->is($variantRepository->findByCode($question->id, 'A')));
        $this->assertTrue($itemRepository->getByVariant($variant->id)->first()->relationLoaded('questionOptions'));
        $this->assertTrue($optionRepository->getByQuestionItem($item->id)->contains($option));
    }

    public function test_learning_and_identity_queries_use_composite_domain_keys(): void
    {
        $participant = User::factory()->create([
            'nip' => '197001010001',
            'email' => 'participant-repository@example.test',
        ]);
        $course = Course::factory()->open()->create();

        $userRepository = $this->app->make(UserRepositoryInterface::class);
        $userMetaRepository = $this->app->make(UserMetaRepositoryInterface::class);
        $enrollmentRepository = $this->app->make(EnrollmentRepositoryInterface::class);
        $learnRepository = $this->app->make(LearnRepositoryInterface::class);
        $learnItemRepository = $this->app->make(LearnItemRepositoryInterface::class);
        $activityRepository = $this->app->make(ActivityRepositoryInterface::class);

        $meta = $userMetaRepository->create([
            'user_id' => $participant->id,
            'key' => 'unit_name',
            'value' => 'Repository Unit',
        ]);
        $enrollment = $enrollmentRepository->create([
            'participant_id' => $participant->id,
            'course_id' => $course->id,
            'schedule_id' => null,
            'status' => 'active',
            'enrolled_at' => now(),
            'access_starts_at' => now()->subMinute(),
            'access_ends_at' => null,
        ]);
        $learn = $learnRepository->create([
            'enrollment_id' => $enrollment->id,
            'participant_id' => $participant->id,
            'course_id' => $course->id,
        ]);
        $activity = $activityRepository->create([
            'course_id' => $course->id,
            'type' => 'video',
            'title' => 'Repository Video',
            'value' => 0,
        ]);
        $learnItem = $learnItemRepository->create([
            'learn_id' => $learn->id,
            'activity_id' => $activity->id,
            'title' => $activity->title,
            'type' => $activity->type,
            'status' => 'available',
        ]);

        $this->assertTrue($participant->is($userRepository->findByNip('197001010001')));
        $this->assertTrue($participant->is($userRepository->findByEmail('participant-repository@example.test')));
        $this->assertTrue($meta->is($userMetaRepository->findByUserAndKey($participant->id, 'unit_name')));
        $this->assertTrue($enrollment->is($enrollmentRepository->findByParticipantAndCourse($participant->id, $course->id)));
        $this->assertTrue($learn->is($learnRepository->findByParticipantAndCourse($participant->id, $course->id)));
        $this->assertTrue($learnItem->is($learnItemRepository->findByLearnAndActivity($learn->id, $activity->id)));
        $courseRepository = $this->app->make(CourseRepositoryInterface::class);
        $this->assertTrue($courseRepository->getByParticipant($participant->id)->contains($course));
    }
}
