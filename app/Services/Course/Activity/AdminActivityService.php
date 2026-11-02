<?php

namespace App\Services\Course\Activity;

use App\Models\Course\Activity\Activity;
use App\Models\Course\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminActivityService
{
    public function __construct(private readonly ActivityService $activity, private readonly QuestionService $question, private readonly InteractiveService $interactive, private readonly TaskService $task, private readonly LiteratureService $literature) {}

    public function storePreTest(array $data, Course $course): void
    {
        DB::transaction(function () use ($data, $course) {
            $activity = $this->activity->createActivity([
                'course_id' => $course->id,
                'type' => 'pre-test',
                'title' => ($data['title'] ?? null),
                'value' => ($data['value'] ?? null),
                'timer' => ($data['timer'] ?? null) * 60,
                'instruction' => ($data['instruction'] ?? null),
            ]);

            $this->question->createQuestion([
                'activity_id' => $activity->id,
                'description' => ($data['description'] ?? null),
                'type' => 'pre',
                'min_score' => ($data['min_score'] ?? null),
                'is_loop' => ($data['repeatable'] ?? null),
            ]);
        });
    }

    public function updatePreTest(Course $course, Activity $pre_test, array $data): void
    {
        DB::transaction(function () use ($pre_test, $data) {
            $activity = $this->activity->updateActivity([
                'activity_id' => $pre_test->id,
                'title' => ($data['title'] ?? null),
                'value' => ($data['value'] ?? null),
                'timer' => ($data['timer'] ?? null) * 60,
                'instruction' => ($data['instruction'] ?? null),
            ]);

            $this->question->updateQuestion([
                'activity_id' => $pre_test->id,
                'type' => 'pre',
                'description' => ($data['description'] ?? null),
                'min_score' => ($data['min_score'] ?? null),
                'is_loop' => ($data['repeatable'] ?? null),
            ]);
        });
    }

    public function storePostTest(array $data, Course $course): void
    {
        DB::transaction(function () use ($data, $course) {
            $activity = $this->activity->createActivity([
                'course_id' => $course->id,
                'type' => 'post-test',
                'title' => ($data['title'] ?? null),
                'value' => ($data['value'] ?? null),
                'timer' => ($data['timer'] ?? null) * 60,
                'instruction' => ($data['instruction'] ?? null),
            ]);

            $this->question->createQuestion([
                'activity_id' => $activity->id,
                'description' => ($data['description'] ?? null),
                'type' => 'post',
                'min_score' => ($data['min_score'] ?? null),
                'is_loop' => ($data['is_loop'] ?? null),
            ]);
        });
    }

    public function updatePostTest(Course $course, Activity $post_test, array $data): void
    {
        DB::transaction(function () use ($post_test, $data) {
            $activity = $this->activity->updateActivity([
                'activity_id' => $post_test->id,
                'title' => ($data['title'] ?? null),
                'value' => ($data['value'] ?? null),
                'timer' => ($data['timer'] ?? null) * 60,
                'instruction' => ($data['instruction'] ?? null),
            ]);

            $this->question->updateQuestion([
                'activity_id' => $post_test->id,
                'type' => 'post',
                'description' => ($data['description'] ?? null),
                'min_score' => ($data['min_score'] ?? null),
                'is_loop' => ($data['is_loop'] ?? null),
            ]);
        });
    }

    public function storePresentationVideo(array $data, Course $course): void
    {
        DB::transaction(function () use ($data, $course) {
            $activity = $this->activity->createActivity([
                'course_id' => $course->id,
                'type' => 'video',
                'title' => ($data['title'] ?? null),
                'value' => ($data['value'] ?? null),
                'instruction' => ($data['instruction'] ?? null),
            ]);

            $this->interactive->createInteractive([
                'activity_id' => $activity->id,
                'type' => 'video',
                'description' => ($data['description'] ?? null),
                'url' => ($data['url'] ?? null),
                'duration' => (int) round(($data['duration'] ?? null) * 60),
            ]);
        });
    }

    public function updatePresentationVideo(Course $course, Activity $video, array $data): void
    {
        DB::transaction(function () use ($video, $data) {
            $activity = $this->activity->updateActivity([
                'activity_id' => $video->id,
                'title' => ($data['title'] ?? null),
                'value' => ($data['value'] ?? null),
                'instruction' => ($data['instruction'] ?? null),
            ]);

            $this->interactive->updateInteractive([
                'activity_id' => $video->id,
                'type' => 'video',
                'description' => ($data['description'] ?? null),
                'url' => ($data['url'] ?? null),
                'duration' => (int) round(($data['duration'] ?? null) * 60),
            ]);
        });
    }

    public function storeOnlineMeeting(array $data, Course $course): void
    {
        DB::transaction(function () use ($data, $course) {
            $activity = $this->activity->createActivity([
                'course_id' => $course->id,
                'type' => 'meeting',
                'title' => ($data['title'] ?? null),
                'value' => ($data['value'] ?? null),
                'instruction' => ($data['instruction'] ?? null),
            ]);

            $this->interactive->createInteractive([
                'activity_id' => $activity->id,
                'type' => 'meeting',
                'description' => ($data['description'] ?? null),
                'url' => ($data['url'] ?? null),
                'duration' => (int) round(($data['duration'] ?? null) * 60),
            ]);
        });
    }

    public function updateOnlineMeeting(Course $course, Activity $meeting, array $data): void
    {
        DB::transaction(function () use ($meeting, $data) {
            $activity = $this->activity->updateActivity([
                'activity_id' => $meeting->id,
                'title' => ($data['title'] ?? null),
                'value' => ($data['value'] ?? null),
                'instruction' => ($data['instruction'] ?? null),
            ]);

            $this->interactive->updateInteractive([
                'activity_id' => $meeting->id,
                'type' => 'meeting',
                'description' => ($data['description'] ?? null),
                'url' => ($data['url'] ?? null),
                'duration' => (int) round(($data['duration'] ?? null) * 60),
            ]);
        });
    }

    public function endMeetingOnlineMeeting(Course $course, string $activityId): void
    {
        DB::transaction(function () use ($activityId) {
            $this->interactive->endMeeting($activityId);
        });
    }

    public function storeTask(array $data, Course $course): void
    {
        DB::transaction(function () use ($data, $course) {
            $activity = $this->activity->createActivity([
                'course_id' => $course->id,
                'type' => 'task',
                'title' => ($data['title'] ?? null),
                'value' => ($data['value'] ?? null),
                'instruction' => ($data['instruction'] ?? null),
            ]);

            $this->task->createTask([
                'activity_id' => $activity->id,
                'description' => ($data['description'] ?? null),
            ]);
        });
    }

    public function updateTask(Course $course, Activity $task, array $data): void
    {
        DB::transaction(function () use ($task, $data) {
            $activity = $this->activity->updateActivity([
                'activity_id' => $task->id,
                'title' => ($data['title'] ?? null),
                'value' => ($data['value'] ?? null),
                'instruction' => ($data['instruction'] ?? null),
            ]);

            $this->task->updateTask([
                'activity_id' => $task->id,
                'description' => ($data['description'] ?? null),
            ]);
        });
    }

    public function storeLiterature(array $data, Course $course, array $context): void
    {
        $stage = 'activity_insert';
        try {

            $activity = DB::transaction(function () use ($data, $course, &$stage) {
                $activity = $this->activity->createActivity([
                    'course_id' => $course->id,
                    'type' => 'literature',
                    'title' => ($data['title'] ?? null),
                    'value' => ($data['value'] ?? null),
                    'instruction' => ($data['instruction'] ?? null),
                ]);

                $stage = 'pdf_upload';
                $this->literature->createLiterature([
                    'activity_id' => $activity->id,
                    'sub_title' => ($data['sub_title'] ?? null),
                    'content' => ($data['content'] ?? null),
                    'duration' => (int) round(($data['duration'] ?? null) * 60),
                ]);

                $stage = 'commit';

                return $activity;
            });

            Log::channel('audit')->info('Literatur berhasil disimpan.', $context + ['event' => 'literature.store.saved', 'activity_id' => $activity->id]);
        } catch (\Throwable $exception) {
            Log::channel('audit')->error('Penyimpanan literatur gagal.', $context + ['event' => 'literature.store.failed', 'stage' => $stage, 'exception' => $exception]);
            throw $exception;
        }
    }

    public function updateLiterature(Course $course, Activity $literature, array $data, array $context): void
    {
        $stage = 'activity_update';
        try {

            $activity = DB::transaction(function () use ($literature, $data, &$stage) {
                $activity = $this->activity->updateActivity([
                    'activity_id' => $literature->id,
                    'title' => ($data['title'] ?? null),
                    'value' => ($data['value'] ?? null),
                    'instruction' => ($data['instruction'] ?? null),
                ]);

                $stage = 'pdf_upload';
                $this->literature->updateLiterature([
                    'activity_id' => $activity->id,
                    'sub_title' => ($data['sub_title'] ?? null),
                    'content' => ($data['content'] ?? null),
                    'duration' => (int) round(($data['duration'] ?? null) * 60),
                ]);

                $stage = 'commit';

                return $activity;
            });

            Log::channel('audit')->info('Literatur berhasil diperbarui.', $context + ['event' => 'literature.update.saved', 'activity_id' => $activity->id]);
        } catch (\Throwable $exception) {
            Log::channel('audit')->error('Pembaruan literatur gagal.', $context + ['event' => 'literature.update.failed', 'stage' => $stage, 'exception' => $exception]);
            throw $exception;
        }
    }
}
