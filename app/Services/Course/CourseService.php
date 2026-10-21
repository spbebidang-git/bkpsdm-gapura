<?php

namespace App\Services\Course;

use App\Helper\UploadMedia;
use App\Models\Course\Course;
use App\Repositories\Course\Activity\Assessment\Contracts\QuestionRepositoryInterface;
use App\Repositories\Course\Activity\Assessment\Contracts\QuestionVariantRepositoryInterface;
use App\Repositories\Course\Activity\Contracts\ActivityRepositoryInterface;
use App\Repositories\Course\Contracts\CourseRepositoryInterface;
use App\Repositories\Course\Contracts\ScheduleRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Identity\User;

class CourseService
{
    public function __construct(
        private readonly CourseRepositoryInterface $courses,
        private readonly ScheduleRepositoryInterface $schedules,
        private readonly ActivityRepositoryInterface $activities,
        private readonly QuestionRepositoryInterface $questions,
        private readonly QuestionVariantRepositoryInterface $variants,
    ) {}

    public function getFeaturedCourses(int $limit = 6, ?User $user = null)
    {
        return $this->courses->getFeaturedPublishedCourses(Carbon::now(), $limit, $user)
            ->map(function ($row) {
                $ratingCount = (int) $row->rating_count;
                $rating = $ratingCount > 0
                    ? round(((float) $row->rating_total) / $ratingCount, 1)
                    : 0;

                return [
                    'id' => $row->id,
                    'type' => $row->type,
                    'title' => $row->title,
                    'employee_status' => $row->employeeStatus->pluck('title')->map(fn ($t) => trim($t))->join(', '),
                    'short_description' => $row->short_description,
                    'slug' => $row->slug,
                    'quota' => $row->quota,
                    'thumbnail' => $row->thumbnail_src,
                    'category' => optional($row->category)->title,
                    'total_participant' => (int) $row->total_participant_count,
                    'total_passed' => (int) $row->total_passed_count,
                    'status' => $row->status,
                    ...$row->audienceData(),
                    'star' => $rating,
                ];
            });
    }

    // public function getAllCourse()
    // {
    //     $now = Carbon::now();
    //     $today = $now->toDateString();
    //     $currentTime = $now->format('H:i:s');

    //     return Course::query()
    //         ->orderBy('id')
    //         ->where('status', 'publish')
    //         ->where(function ($q) use ($today, $currentTime) {
    //             $q->where('type', '!=', 'schedule')
    //                 ->orWhereHas('schedules', function ($query) use ($today, $currentTime) {
    //                     $query->whereDate('date', $today)
    //                         ->whereTime('time_from', '<=', $currentTime)
    //                         ->whereTime('time_to', '>=', $currentTime);
    //                 });
    //         })
    //         ->get()
    //         ->map(function ($row) {
    //             return [
    //                 'id' => $row->id,
    //                 'type' => $row->type,
    //                 'title' => $row->title,
    //                 'employee_status' => $row->employee_status,
    //                 'slug' => $row->slug,
    //                 'quota' => $row->quota,
    //                 'thumbnail' => $row->thumbnail_src,
    //                 'category' => optional($row->category)->title,
    //                 'instructor' => optional($row->instructor)->name,
    //                 'total_participant' => $row->total_participant,
    //                 'status' => $row->status,
    //                 'star' => $row->getRating()['star'],
    //             ];
    //         });
    // }

    public function getAllCourse(array $filters = [], ?User $user = null)
    {
        return $this->courses->getPublishedAvailable(Carbon::now(), filters: $filters, user: $user)
            ->map(function ($row) {
                $ratingCount = (int) $row->rating_count;
                $rating = $ratingCount > 0
                    ? round(((float) $row->rating_total) / $ratingCount, 1)
                    : 0;

                return [
                    'id' => $row->id,
                    'type' => $row->type,
                    'title' => $row->title,
                    'employee_status' => $row->employeeStatus->pluck('title')->map(fn ($t) => trim($t))->join(', '),
                    'short_description' => $row->short_description,
                    'slug' => $row->slug,
                    'quota' => $row->quota,
                    'thumbnail' => $row->thumbnail_src,
                    'category' => optional($row->category)->title,
                    'total_participant' => (int) $row->total_participant_count,
                    'total_passed' => (int) $row->total_passed_count,
                    'status' => $row->status,
                    ...$row->audienceData(),
                    'star' => $rating,
                ];
            });
    }

    public function getByInstructor($instructorId = null)
    {
        return $this->getByAdmin($instructorId);
    }

    public function getByParticipant($participantId)
    {
        return $this->schedules->getByParticipant($participantId)
            ->filter(fn ($row) => $row->course !== null)
            ->map(function ($row) {
                return [
                    'id' => $row->course->id,
                    'type' => $row->course->type,
                    'title' => $row->course->title,
                    'slug' => $row->course->slug,
                    'quota' => $row->course->quota,
                    'thumbnail' => $row->course->thumbnail_src,
                    'short_description' => $row->course->short_description,
                    'description' => $row->course->description,
                    'category' => optional($row->course->category)->title,
                    'total_participant' => $row->course->total_participant,
                    'status' => $row->course->status,
                    ...$row->course->audienceData(),
                    'schedule_id' => $row->id,
                    'initial_activity' => $row->course->activities->first() ? $row->course->activities->first()->id : 0,
                    'employee_status' => $row->course->employeeStatus->pluck('title')->join(', '),
                    'finished_at' => $row->course->learns->first()->finished_at ?? null,
                ];
            })
            ->values();
    }

    public function getByAdmin($userId = null)
    {
        return $this->courses->getForInstructor($userId)
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'type' => $row->type,
                    'title' => $row->title,
                    'slug' => $row->slug,
                    'quota' => $row->quota,
                    'thumbnail' => $row->thumbnail_src,
                    'category' => optional($row->category)->title,
                    'total_participant' => $row->total_participant,
                    'status' => $row->status,
                    ...$row->audienceData(),
                    'employee_status' => $row->employeeStatus->pluck('title')->join(', '),
                    'schedules_count' => (int) $row->schedules_count,
                    'updated_at' => $row->updated_at,
                    'modified_by' => $row->modified_by,
                    'modifier_name' => $row->modifier?->name,
                ];
            });
    }

    public function findBySlug($slug, ?User $user = null)
    {
        return $this->courses->findVisibleBySlug($slug, $user, ['reviews', 'opds', 'employeeStatus']);
    }

    public function createCourse($data)
    {
        return DB::transaction(function () use ($data) {
            $course = $this->courses->create([
                'category_id' => $data['category'],
                'access_mode' => Course::mapTypeToAccessMode($data['type'] ?? null),
                'title' => $data['title'],
                'slug' => str()->slug($data['title']),
                'short_description' => sanitize_plain_text($data['short_description']),
                'description' => sanitize_html($data['description']),
                'thumbnail' => UploadMedia::file($data['thumbnail'], Course::$directoryImage),
                'teaser_video' => $data['teaser_video'],
                'default_access_hours' => $data['expired'] ?? null,
                'jam_pelajaran' => $data['jam_pelajaran'],
                'status' => $data['status'],
                'visibility' => $data['visibility'] ?? 'private',
                'opd_id' => null,
                'updated_at' => now(),
            ]);

            $this->courses->syncEmployeeStatuses($course, $data['employee_status']);
            $this->courses->syncTrainers($course, $data['trainers'] ?? []);
            $course->opds()->sync(($data['visibility'] ?? 'private') === 'private' ? ($data['opd_ids'] ?? []) : []);

            $this->ensureOpenCourseSchedule($course);

            return true;
        });
    }

    public function updateCourse($course, $data)
    {
        return DB::transaction(function () use ($course, $data) {
            $course = $this->courses->update($course, [
                'category_id' => $data['category'],
                'access_mode' => Course::mapTypeToAccessMode($data['type'] ?? null),
                'title' => $data['title'],
                // 'slug' => str()->slug($data['title']),
                'short_description' => sanitize_plain_text($data['short_description']),
                'description' => sanitize_html($data['description']),
                'teaser_video' => $data['teaser_video'],
                'default_access_hours' => ($data['expired'] ?? null) == 'undefined' ? null : ($data['expired'] ?? null),
                'jam_pelajaran' => $data['jam_pelajaran'],
                'status' => $data['status'],
                'visibility' => $data['visibility'] ?? 'private',
                'opd_id' => null,
                'updated_at' => now(),
            ]);

            $this->courses->syncEmployeeStatuses($course, $data['employee_status']);
            $this->courses->syncTrainers($course, $data['trainers'] ?? []);
            $course->opds()->sync(($data['visibility'] ?? 'private') === 'private' ? ($data['opd_ids'] ?? []) : []);

            if (isset($data['thumbnail'])) {
                if (is_file_exists(Course::$directoryImage.$course->thumbnail)) {
                    remove_file(Course::$directoryImage.$course->thumbnail);
                }

                $course->thumbnail = UploadMedia::file($data['thumbnail'], Course::$directoryImage);
                $course = $this->courses->update($course, ['thumbnail' => $course->thumbnail]);
            }
            $this->ensureOpenCourseSchedule($course);

            return true;
        });
    }

    /**
     * Open courses still use a schedule as the enrolment container.  It has no
     * date, time, or quota, so participants can activate the course at any time.
     */
    private function ensureOpenCourseSchedule(Course $course): void
    {
        if ($course->access_mode !== 'open' || $this->schedules->getByCourse($course->id)->isNotEmpty()) {
            return;
        }

        $this->schedules->create(['course_id' => $course->id]);
    }

    public function findForManagementOverview(int|string $courseId): ?Course
    {
        return $this->courses->findForManagementOverview($courseId);
    }

    public function findForManagementDetail(int|string $courseId): ?Course
    {
        return $this->courses->findForManagementDetail($courseId);
    }

    public function canBeManagedByTrainer(Course $course, int|string $userId): bool
    {
        return $this->courses->isManagedByTrainer($course, $userId);
    }

    public function deleteCourse(Course $course): bool
    {
        return $this->courses->delete($course);
    }

    public function lockCourse(Course $course): bool
    {
        if ($course->is_locked) {
            return false;
        }

        $this->courses->update($course, ['is_locked' => true]);

        return true;
    }

    public function unlockCourse(Course $course): bool
    {
        if (! $course->is_locked) {
            return false;
        }

        $this->courses->update($course, ['is_locked' => false]);

        return true;
    }

    public function generatePrePostTestVariant($participantId, $course)
    {
        // Ambil semua activity pre-test/post-test untuk course ini
        $activities = $this->activities->getByCourseAndTypes($course->id, ['pre-test', 'post-test']);

        foreach ($activities as $activity) {
            $question = $this->questions->findByActivity($activity->id);
            if (! $question) {
                continue;
            }

            // Ambil semua variant beserta jumlah soalnya, hanya yang punya soal
            $variants = $this->variants->getByQuestionWithItemCount($question->id)
                ->filter(fn ($v) => $v->question_items_count > 0);

            if ($variants->isEmpty()) {
                continue;
            }

            // Cari jumlah soal terbanyak
            $maxCount = $variants->max('question_items_count');

            // Filter hanya variant yang jumlah soalnya sama dengan max
            $eligibleVariants = $variants->where('question_items_count', $maxCount);

            // Pilih random dari yang eligible
            $selectedVariant = $eligibleVariants->random();

            update_user_meta($participantId, $activity->id, $selectedVariant->id);
        }
    }

    // public function checkEmployeeStatusCourse($userId, $course)
    // {
    //     # get meta employee status
    //     $employeeStatus = get_user_meta($userId, 'status_pegawai');

    //     if ($employeeStatus) {
    //         if ($course->employee_status === $employeeStatus) {
    //             return true;
    //         }
    //     }

    //     return false;
    // }

    public function checkEmployeeStatusCourse($userId, $course)
    {
        $user = \App\Models\Identity\User::find($userId);
        return $user && app(CourseAudience::class)->matchesStatus($course, $user);
    }
}
