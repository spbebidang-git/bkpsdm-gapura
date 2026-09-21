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

class CourseService
{
    public function __construct(
        private readonly CourseRepositoryInterface $courses,
        private readonly ScheduleRepositoryInterface $schedules,
        private readonly ActivityRepositoryInterface $activities,
        private readonly QuestionRepositoryInterface $questions,
        private readonly QuestionVariantRepositoryInterface $variants,
    ) {}

    public function getRandomCourse()
    {
        return $this->courses->getPublishedActiveAt(Carbon::now())
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
                    'star' => $row->getRating()['star'],
                    'total_passed' => $row->total_passed,
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

    public function getAllCourse()
    {
        return $this->courses->getPublishedAvailable(Carbon::now())
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'type' => $row->type,
                    'title' => $row->title,
                    'employee_status' => $row->employeeStatus->pluck('title')->map(fn ($t) => trim($t))->join(', '),
                    'slug' => $row->slug,
                    'quota' => $row->quota,
                    'thumbnail' => $row->thumbnail_src,
                    'category' => optional($row->category)->title,
                    'total_participant' => $row->total_participant,
                    'status' => $row->status,
                    'star' => $row->getRating()['star'],
                ];
            });
    }

    /** @deprecated ownership gone — all courses admin-managed */
    public function getByInstructor($instructorId = null)
    {
        return $this->getByAdmin();
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
                    'schedule_id' => $row->id,
                    'initial_activity' => $row->course->activities->first() ? $row->course->activities->first()->id : 0,
                    'finished_at' => $row->course->learns->first()->finished_at ?? null,
                ];
            })
            ->values();
    }

    public function getByAdmin()
    {
        return $this->courses->getForInstructor()
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
                    'updated_at' => $row->updated_at,
                ];
            });
    }

    public function findBySlug($slug)
    {
        return $this->courses->findBySlug($slug, ['reviews']);
    }

    public function createCourse($data, $schedules = null)
    {
        return DB::transaction(function () use ($data, $schedules) {
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
            ]);

            $this->courses->syncEmployeeStatuses($course, $data['employee_status']);
            $this->courses->syncTrainers($course, $data['trainers'] ?? []);

            if ($schedules) {
                foreach ($schedules as $item) {
                    $this->schedules->create([
                        'course_id' => $course->id,
                        'date' => $item['date'],
                        'time_from' => $item['time_from'],
                        'time_to' => $item['time_to'],
                        'quota' => $item['quota'],
                    ]);
                }

                return true;
            }

            $this->schedules->create(['course_id' => $course->id]);

            return true;
        });
    }

    public function updateCourse($course, $data, $schedules = null)
    {
        return DB::transaction(function () use ($course, $data, $schedules) {
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
            ]);

            $this->courses->syncEmployeeStatuses($course, $data['employee_status']);
            $this->courses->syncTrainers($course, $data['trainers'] ?? []);

            if (isset($data['thumbnail'])) {
                if (is_file_exists(Course::$directoryImage.$course->thumbnail)) {
                    remove_file(Course::$directoryImage.$course->thumbnail);
                }

                $course->thumbnail = UploadMedia::file($data['thumbnail'], Course::$directoryImage);
                $course = $this->courses->update($course, ['thumbnail' => $course->thumbnail]);
            }
            // dd($schedules);
            if ($schedules) {
                $keepIds = [];
                $existingIds = $this->schedules->getByCourse($course->id)->pluck('id')->all();

                foreach ($schedules as $item) {
                    $normTime = function ($value) {
                        if ($value === null || $value === '') {
                            return null;
                        }
                        try {
                            return Carbon::parse($value)->format('H:i');
                        } catch (\Throwable $e) {
                            return $value;
                        }
                    };

                    $payload = [
                        'course_id' => $course->id,
                        'date' => ! empty($item['date']) ? Carbon::parse($item['date'])->format('Y-m-d') : null,
                        'time_from' => $normTime($item['time_from'] ?? null),
                        'time_to' => $normTime($item['time_to'] ?? null),
                        'quota' => $item['quota'] ?? null,
                    ];

                    // Only update real DB rows — never insert frontend Date.now() ids as PK
                    $dbId = isset($item['id']) && in_array((string) $item['id'], $existingIds, true)
                        ? (string) $item['id']
                        : null;

                    if ($dbId) {
                        $schedule = $this->schedules->findByCourse($course->id, $dbId);
                        $schedule = $this->schedules->update($schedule, $payload);
                    } else {
                        $schedule = $this->schedules->create($payload);
                    }

                    $keepIds[] = $schedule->id;
                }

                // Soft-delete jadwal yang tidak ada di kiriman terbaru
                $this->schedules->deleteMissingForCourse($course->id, $keepIds);

                return true;
            }

            return true;
        });
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
        $employeeStatus = get_user_meta($userId, 'status_pegawai');
        // ponytail: if user has no status_pegawai meta (SSO sync missed),
        // allow activation. Course restriction is defense-in-depth, not auth.
        if (! $employeeStatus) {
            return true;
        }

        $allowedStatuses = $course->employeeStatus->pluck('title')->toArray();
        // dd($employeeStatus, $allowedStatuses);
        // No restrictions configured — allow all.
        if (empty($allowedStatuses)) {
            return true;
        }

        // Case-insensitive + trim — SSO returns " Pns ", DB stores "PNS"
        return in_array(strtoupper(trim($employeeStatus)), array_map(fn ($s) => strtoupper(trim($s)), $allowedStatuses));
    }
}
