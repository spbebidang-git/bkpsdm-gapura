<?php

namespace App\Services\Course;

use App\Helper\UploadMedia;
use App\Models\Course\Activity;
use App\Models\Course\Course;
use App\Models\Course\Question;
use App\Models\Course\Schedule;
use Illuminate\Support\Facades\DB;

class CourseService
{
    public function getRandomCourse()
    {
        return Course::inRandomOrder()
            ->limit(8)
            ->where('status', 'publish')
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
                    'star' => $row->getRating()['star'],
                    'total_participant' => $row->total_participant,
                    'total_passed' => $row->total_passed,
                ];
            });
    }

    public function getAllCourse()
    {
        return Course::OrderBy('id')
            ->where('status', 'publish')
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
                    'star' => $row->getRating()['star']
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
        return Schedule::whereRelation('participants', 'participant_id', '=', $participantId)
            ->with(['course', 'course.learns' => function ($query) use ($participantId) {
                $query->where('participant_id', $participantId);
            }, 'course.activities'])
            ->get()
            ->map(function ($row) {
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
                    'schedule_id' => 1,
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
            'instructor_id' => $instructorId,
            'type' => $data['type'],
            'title' => $data['title'],
            'slug' => str()->slug($data['title']),
            'short_description' => $data['short_description'],
            'description' => $data['description'],
            'thumbnail' => UploadMedia::file($data['thumbnail'], Course::$directoryImage),
            'teaser_video' => $data['teaser_video'],
            'expired' => $data['expired'],
            'status' => $data['status'],
        ]);

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
        // dd($data['expired']);
        $course->update([
            'category_id' => $data['category'],
            'type' => $data['type'],
            'title' => $data['title'],
            'slug' => str()->slug($data['title']),
            'short_description' => $data['short_description'],
            'description' => $data['description'],
            'teaser_video' => $data['teaser_video'],
            'expired' => $data['expired'] == 'undefined' ? null :  $data['expired'],
            'status' => $data['status'],
        ]);

        if ($data['thumbnail']) {
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
        $queryActivities = DB::table('activities')
            ->select('activities.id as key', DB::raw('MAX(question_variants.id) as value'))
            ->join('questions', 'questions.activity_id', '=', 'activities.id')
            ->join('question_variants', 'question_variants.question_id', '=', 'questions.id')
            ->where('activities.course_id', $course->id)
            ->where(function ($query) {
                $query->where('activities.type', 'pre-test')
                    ->orWhere('activities.type', 'post-test');
            })->groupBy('activities.id')->get();

        $queryVariants = DB::table('activities')
            ->select('activities.id as key', DB::raw('MAX(question_variants.id) as value'))
            ->join('questions', 'questions.activity_id', '=', 'activities.id')
            ->join('question_variants', 'question_variants.question_id', '=', 'questions.id')
            ->where('activities.course_id', $course->id)
            ->where(function ($query) {
                $query->where('activities.type', 'pre-test')
                    ->orWhere('activities.type', 'post-test');
            })
            ->groupBy('question_variants.id')
            ->inRandomOrder()
            ->get(); // Mengubah menjadi collection

        // dd($queryActivities);
        # group by activity
        $data = [];
        foreach ($queryActivities as $item) {
            $variant = $queryVariants->firstWhere('key', $item->key);
            // dd($variant->value);
            // $data[] = [
            //     'key' => $item->key,
            //     'value' => $variant->value, // Sesuaikan nama kolom 'value' sesuai data yang ada di $variant
            // ];
            update_user_meta($participantId, $item->key, $variant->value);
        }

        // dd($data);

        # loop data 



        // dd($queryKeyValue);
    }
}
