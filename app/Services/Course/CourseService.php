<?php

namespace App\Services\Course;

use App\Helper\UploadMedia;
use App\Models\Course\Activity;
use App\Models\Course\Course;
use App\Models\Course\Question;
use App\Models\Course\Question\QuestionVariant;
use App\Models\Course\Schedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CourseService
{
    public function getRandomCourse()
    {
        $now = Carbon::now();
        $today = $now->toDateString();
        $currentTime = $now->format('H:i:s');

        return Course::query()
            ->inRandomOrder()
            ->where('status', 'publish')
            ->where(function ($q) use ($today, $currentTime) {
                $q->where('type', '!=', 'schedule')
                    ->orWhereHas('schedules', function ($query) use ($today, $currentTime) {
                        $query->whereDate('date', $today)
                            ->whereTime('time_from', '<=', $currentTime)
                            ->whereTime('time_to', '>=', $currentTime);
                    });
            })
            ->limit(8)
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'type' => $row->type,
                    'title' => $row->title,
                    'slug' => $row->slug,
                    'quota' => $row->quota,
                    'thumbnail' => $row->thumbnail_src,
                    'category' => optional($row->category)->title,
                    'instructor' => optional($row->instructor)->name,
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
        $now = Carbon::now();
        $today = $now->toDateString();
        $currentTime = $now->format('H:i:s');

        return Course::query()
            ->orderBy('id')
            ->where('status', 'publish')
            ->where(function ($q) use ($today, $currentTime) {
                // Tampilkan yang tipenya bukan schedule (Video/Modul)
                $q->where('type', '!=', 'schedule')
                    // ATAU yang punya jadwal aktif/mendatang
                    ->orWhereHas('schedules', function ($query) use ($today, $currentTime) {
                        $query->where(function ($sub) use ($today, $currentTime) {
                            // Kondisi 1: Jadwal di hari-hari mendatang (Besok dan seterusnya)
                            $sub->where('date', '>', $today)
                                // Kondisi 2: Jadwal hari ini, tapi waktunya belum berakhir (masih berlangsung atau akan datang)
                                ->orWhere(function ($dayQuery) use ($today, $currentTime) {
                                    $dayQuery->whereDate('date', $today)
                                        ->whereTime('time_to', '>=', $currentTime);
                                });
                        });
                    });
            })
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'type' => $row->type,
                    'title' => $row->title,
                    'employee_status' => $row->employee_status,
                    'slug' => $row->slug,
                    'quota' => $row->quota,
                    'thumbnail' => $row->thumbnail_src,
                    'category' => optional($row->category)->title,
                    'instructor' => optional($row->instructor)->name,
                    'total_participant' => $row->total_participant,
                    'status' => $row->status,
                    'star' => $row->getRating()['star'],
                ];
            });
    }

    public function getByInstructor($instructodId)
    {
        return Course::where('instructor_id', $instructodId)
            // ->with('schedules')
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'type' => $row->type,
                    'title' => $row->title,
                    'slug' => $row->slug,
                    'quota' => $row->quota,
                    'thumbnail' => $row->thumbnail_src,
                    'category' => $row->category->title,
                    'instructor' => $row->instructor->name,
                    'total_participant' => $row->total_participant,
                    'status' => $row->status,
                ];
            });
    }

    public function getByParticipant($participantId)
    {
        // dd($participantId);
        return Schedule::whereRelation('participants', 'participant_id', '=', $participantId)
            ->with(['course', 'course.learns' => function ($query) use ($participantId) {
                $query->where('participant_id', $participantId);
            }, 'course.activities'])
            ->get()
            ->map(function ($row) {
                // dd($row->course->id);
                // dd($row->course->activities->first()->id);
                return [
                    'id' => $row->course->id,
                    'type' => $row->course->type,
                    'title' => $row->course->title,
                    'slug' => $row->course->slug,
                    'quota' => $row->course->quota,
                    'thumbnail' => $row->course->thumbnail_src,
                    'short_description' => $row->course->short_description,
                    'description' => $row->course->description,
                    'category' => $row->course->category->title,
                    'instructor' => $row->course->instructor->name,
                    'total_participant' => $row->course->total_participant,
                    'status' => $row->course->status,
                    'schedule_id' => $row->id,
                    'initial_activity' => $row->course->activities->first() ? $row->course->activities->first()->id : 0,
                    'finished_at' => $row->course->learns->first()->finished_at ?? null
                ];
            });
    }

    public function getByAdmin()
    {
        return Course::get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'type' => $row->type,
                    'title' => $row->title,
                    'slug' => $row->slug,
                    'quota' => $row->quota,
                    'thumbnail' => $row->thumbnail_src,
                    'category' => $row->category->title,
                    'instructor' => $row->instructor->name,
                    'total_participant' => $row->total_participant,
                    'status' => $row->status,
                ];
            });
    }

    public function findBySlug($slug)
    {
        return Course::where('slug', $slug)
            ->with('instructor', 'reviews')
            ->first();
    }

    public function createCourse($instructorId, $data, $schedules = null)
    {
        # Create Course
        $course = Course::create([
            'category_id' => $data['category'],
            // 'employee_status' => $data['employee_status'],
            'instructor_id' => $instructorId,
            'type' => $data['type'],
            'title' => $data['title'],
            'slug' => str()->slug($data['title']),
            'short_description' => sanitize_plain_text($data['short_description']),
            'description' => sanitize_plain_text($data['description']),
            'thumbnail' => UploadMedia::file($data['thumbnail'], Course::$directoryImage),
            'teaser_video' => $data['teaser_video'],
            'expired' => $data['expired'],
            'jam_pelajaran' => $data['jam_pelajaran'],
            'status' => $data['status'],
        ]);

        $course->employeeStatus()->attach($data['employee_status']);

        # Create Schedule
        if ($schedules) {
            foreach ($schedules as $item) {
                Schedule::create([
                    'course_id' => $course->id,
                    'date' => $item['date'],
                    'time_from' => $item['time_from'],
                    'time_to' => $item['time_to'],
                    'quota' => $item['quota'],
                ]);
            }

            return true;
        }

        Schedule::create([
            'course_id' => $course->id
        ]);

        return true;
    }

    public function updateCourse($course, $instructorId, $data, $schedules = null)
    {
        $course->update([
            'category_id' => $data['category'],
            // 'employee_status' => $data['employee_status'],
            'type' => $data['type'],
            'title' => $data['title'],
            'slug' => str()->slug($data['title']),
            'short_description' => sanitize_plain_text($data['short_description']),
            'description' => sanitize_plain_text($data['description']),
            'teaser_video' => $data['teaser_video'],
            'expired' => $data['expired'] == 'undefined' ? null :  $data['expired'],
            'jam_pelajaran' => $data['jam_pelajaran'],
            'status' => $data['status'],
        ]);

        $course->employeeStatus()->sync($data['employee_status']);

        if (isset($data['thumbnail'])) {
            if (is_file_exists(Course::$directoryImage . $course->thumbnail)) {
                remove_file(Course::$directoryImage . $course->thumbnail);
            }

            $course->thumbnail = UploadMedia::file($data['thumbnail'], Course::$directoryImage);
            $course->save();
        }
        // dd($schedules);
        # Update Schedule
        if ($schedules) {
            # Remove all schedules by course
            // dd($course->load('schedules'));
            // Memuat relasi schedules
            $course->load('schedules');

            // Menghapus setiap schedule terkait
            $course->schedules->each(function ($schedule) {
                $schedule->delete();  // Menghapus setiap schedule
            });
            foreach ($schedules as $item) {
                Schedule::create([
                    'course_id' => $course->id,
                    'date' => $item['date'],
                    'time_from' => $item['time_from'],
                    'time_to' => $item['time_to'],
                    'quota' => $item['quota'],
                ]);
            }

            return true;
        }
    }

    public function generatePrePostTestVariant($participantId, $course)
    {
        // Ambil semua activity pre-test/post-test untuk course ini
        $activities = Activity::where('course_id', $course->id)
            ->whereIn('type', ['pre-test', 'post-test'])
            ->get();

        foreach ($activities as $activity) {
            $question = Question::where('activity_id', $activity->id)->first();
            if (!$question) continue;

            // Ambil semua variant beserta jumlah soalnya, hanya yang punya soal
            $variants = QuestionVariant::withCount('questionItems')
                ->where('question_id', $question->id)
                ->get()
                ->filter(fn($v) => $v->question_items_count > 0);

            if ($variants->isEmpty()) continue;

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
        // Ambil status pegawai user (misalnya: CPNS, PNS, THL, PPPK)
        $employeeStatus = get_user_meta($userId, 'status_pegawai');

        if (!$employeeStatus) {
            return false;
        }

        // Ambil semua status pegawai yang diizinkan untuk course ini
        // Misalnya: ['PNS', 'PPPK']
        $allowedStatuses = $course->employeeStatus->pluck('title')->toArray();

        // Cek apakah status user termasuk dalam daftar
        return in_array($employeeStatus, $allowedStatuses);
    }
}
